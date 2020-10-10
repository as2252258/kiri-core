<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +---------------------------------------------------------------------------
// | SWAN [ $_SWANBR_SLOGAN_$ ]
// +---------------------------------------------------------------------------
// | Copyright $_SWANBR_COPYRIGHT_$
// +---------------------------------------------------------------------------
// | Version  $_SWANBR_VERSION_$
// +---------------------------------------------------------------------------
// | Licensed ( $_SWANBR_LICENSED_URL_$ )
// +---------------------------------------------------------------------------
// | $_SWANBR_WEB_DOMAIN_$
// +---------------------------------------------------------------------------

namespace Kafka;

use HttpServer\Http\Context;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

/**
 * +------------------------------------------------------------------------------
 * Kafka protocol since Kafka v0.8
 * +------------------------------------------------------------------------------
 *
 * @package
 * @version $_SWANBR_VERSION_$
 * @copyright Copyleft
 * @author $_SWANBR_AUTHOR_$
 * +------------------------------------------------------------------------------
 */
class SocketSync
{
	const READ_MAX_LEN = 5242880; // read socket max length 5MB


	const SOCKET_NAME = 'client_socket';

	/**
	 * max write socket buffer
	 * fixed:send of 8192 bytes failed with errno=11 Resource temporarily
	 * fixed:'fwrite(): send of ???? bytes failed with errno=35 Resource temporarily unavailable'
	 * unavailable error info
	 */
	const MAX_WRITE_BUFFER = 2048;

	/**
	 * Send timeout in seconds.
	 *
	 * @var float
	 * @access private
	 */
	private $sendTimeoutSec = 0;

	/**
	 * Send timeout in microseconds.
	 *
	 * @var float
	 * @access private
	 */
	private $sendTimeoutUsec = 100000;

	/**
	 * Recv timeout in seconds
	 *
	 * @var float
	 * @access private
	 */
	private $recvTimeoutSec = 0;


	/**
	 * Recv timeout in microseconds
	 *
	 * @var float
	 * @access private
	 */
	private $recvTimeoutUsec = 750000;

	/**
	 * Stream resource
	 *
	 * @var \Swoole\Coroutine\Socket
	 * @access private
	 */
	private $stream = null;

	/**
	 * Socket host
	 *
	 * @var mixed
	 * @access private
	 */
	private $host = null;

	/**
	 * Socket port
	 *
	 * @var mixed
	 * @access private
	 */
	private $port = -1;

	/**
	 * Max Write Attempts
	 * @var int
	 * @access private
	 */
	private $maxWriteAttempts = 3;


	/**
	 * __construct
	 *
	 * @access public
	 * @param $host
	 * @param $port
	 * @param int $recvTimeoutSec
	 * @param int $recvTimeoutUsec
	 * @param int $sendTimeoutSec
	 * @param int $sendTimeoutUsec
	 */
	public function __construct($host, $port, $recvTimeoutSec = 0, $recvTimeoutUsec = 750000, $sendTimeoutSec = 0, $sendTimeoutUsec = 100000)
	{
		$this->host = $host;
		$this->port = $port;
		$this->setRecvTimeoutSec($recvTimeoutSec);
		$this->setRecvTimeoutUsec($recvTimeoutUsec);
		$this->setSendTimeoutSec($sendTimeoutSec);
		$this->setSendTimeoutUsec($sendTimeoutUsec);
	}

	/**
	 * @param float $sendTimeoutSec
	 */
	public function setSendTimeoutSec(float $sendTimeoutSec)
	{
		$this->sendTimeoutSec = $sendTimeoutSec;
	}

	/**
	 * @param float $sendTimeoutUsec
	 */
	public function setSendTimeoutUsec(float $sendTimeoutUsec)
	{
		$this->sendTimeoutUsec = $sendTimeoutUsec;
	}

	/**
	 * @param float $recvTimeoutSec
	 */
	public function setRecvTimeoutSec(float $recvTimeoutSec)
	{
		$this->recvTimeoutSec = $recvTimeoutSec;
	}

	/**
	 * @param float $recvTimeoutUsec
	 */
	public function setRecvTimeoutUsec(float $recvTimeoutUsec)
	{
		$this->recvTimeoutUsec = $recvTimeoutUsec;
	}

	/**
	 * @param int $number
	 */
	public function setMaxWriteAttempts(int $number)
	{
		$this->maxWriteAttempts = $number;
	}


	/**
	 * Optional method to set the internal stream handle
	 *
	 * @static
	 * @access public
	 * @param $stream
	 * @return SocketSync
	 */
	public static function createFromStream($stream)
	{
		$socket = new self('localhost', 0);
		$socket->setStream($stream);
		return $socket;
	}


	/**
	 * Optional method to set the internal stream handle
	 *
	 * @param mixed $stream
	 * @access public
	 * @return void
	 */
	public function setStream($stream)
	{
		$this->stream = $stream;
	}


	/**
	 * Connects the socket
	 *
	 * @access public
	 * @return Coroutine\Socket
	 */
	public function connect()
	{
		if (Context::hasContext(self::SOCKET_NAME)) {
			return Context::getContext(self::SOCKET_NAME);
		}
		if (empty($this->host)) {
			throw new Exception('Cannot open null host.');
		}
		if ($this->port <= 0) {
			throw new Exception('Cannot open without port.');
		}
		$stream = new \Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM);
		if (!$stream->connect($this->host, $this->port)) {
			$error = 'Could not connect to '
				. $this->host . ':' . $this->port
				. ' (' . $stream->errMsg . ' [' . $stream->errMsg . '])';
			throw new Exception($error);
		}
		return Context::setContext(self::SOCKET_NAME, $stream);
	}


	/**
	 * close the socket
	 *
	 * @access public
	 * @return void
	 */
	public function close()
	{

	}

	/**
	 * checks if the socket is a valid resource
	 *
	 * @access public
	 * @return boolean
	 */
	public function isResource()
	{
		if (!Context::hasContext(self::SOCKET_NAME)) {
			return false;
		}
		return Context::getContext(self::SOCKET_NAME)->checkLiveness();
	}

	/**
	 * Read from the socket at most $len bytes.
	 *
	 * This method will not wait for all the requested data, it will return as
	 * soon as any data is received.
	 *
	 * @param integer $len Maximum number of bytes to read.
	 * @param boolean $verifyExactLength Throw an exception if the number of read bytes is less than $len
	 *
	 * @return string Binary data
	 * @throws Exception
	 */
	public function read(int $len, $verifyExactLength = false)
	{
		if ($len > self::READ_MAX_LEN) {
			throw new Exception('Could not read ' . $len . ' bytes from stream, length too longer.');
		}
		$stream = Context::getContext(self::SOCKET_NAME);

		$null = null;
		$remainingBytes = $len;
		$data = $chunk = '';
		while ($remainingBytes > 0) {
			$chunk = $stream->recv($remainingBytes);
			if ($chunk === false) {
				throw new Exception('Could not read ' . $len . ' bytes from stream (no data)');
			}
			if (strlen($chunk) === 0) {
				continue;
			}
			$data .= $chunk;
			$remainingBytes -= strlen($chunk);
		}
		if ($len === $remainingBytes || ($verifyExactLength && $len !== strlen($data))) {
			throw new Exception('Read ' . strlen($data) . ' bytes instead of the requested ' . $len . ' bytes');
		}
		return $data;
	}

	/**
	 * Write to the socket.
	 *
	 * @param string $buf The data to write
	 *
	 * @return integer
	 * @throws Exception
	 * @throws \Exception
	 */
	public function write(string $buf)
	{
		$null = null;

		$failedWriteAttempts = 0;
		$written = 0;
		$buflen = strlen($buf);

		$stream = $this->connect();
		while ($written < $buflen) {
			if ($buflen - $written > self::MAX_WRITE_BUFFER) {
				$wrote = $stream->send(substr($buf, $written, self::MAX_WRITE_BUFFER), 1);
			} else {
				$wrote = $stream->send(substr($buf, $written), 1);
			}
			if ($wrote === -1 || $wrote === false) {
				throw new \Kafka\Exception\Socket('Could not write ' . strlen($buf) . ' bytes to stream, completed writing only ' . $written . ' bytes');
			} elseif ($wrote === 0) {
				$failedWriteAttempts++;
				if ($failedWriteAttempts > $this->maxWriteAttempts) {
					throw new \Kafka\Exception\Socket('After ' . $failedWriteAttempts . ' attempts could not write ' . strlen($buf) . ' bytes to stream, completed writing only ' . $written . ' bytes');
				}
			} else {
				$failedWriteAttempts = 0;
			}
			$written += $wrote;
		}
		return $written;
	}

}
