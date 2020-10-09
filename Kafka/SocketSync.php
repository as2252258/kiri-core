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

use Snowflake\Event;
use Snowflake\Snowflake;
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
	// {{{ consts

	const READ_MAX_LEN = 5242880; // read socket max length 5MB

	/**
	 * max write socket buffer
	 * fixed:send of 8192 bytes failed with errno=11 Resource temporarily
	 * fixed:'fwrite(): send of ???? bytes failed with errno=35 Resource temporarily unavailable'
	 * unavailable error info
	 */
	const MAX_WRITE_BUFFER = 2048;

	// }}}
	// {{{ members

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

	// }}}
	// {{{ functions
	// {{{ public function __construct()

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
	public function setSendTimeoutSec($sendTimeoutSec)
	{
		$this->sendTimeoutSec = $sendTimeoutSec;
	}

	/**
	 * @param float $sendTimeoutUsec
	 */
	public function setSendTimeoutUsec($sendTimeoutUsec)
	{
		$this->sendTimeoutUsec = $sendTimeoutUsec;
	}

	/**
	 * @param float $recvTimeoutSec
	 */
	public function setRecvTimeoutSec($recvTimeoutSec)
	{
		$this->recvTimeoutSec = $recvTimeoutSec;
	}

	/**
	 * @param float $recvTimeoutUsec
	 */
	public function setRecvTimeoutUsec($recvTimeoutUsec)
	{
		$this->recvTimeoutUsec = $recvTimeoutUsec;
	}

	/**
	 * @param int $number
	 */
	public function setMaxWriteAttempts($number)
	{
		$this->maxWriteAttempts = $number;
	}

	// }}}
	// {{{ public static function createFromStream()

	/**
	 * Optional method to set the internal stream handle
	 *
	 * @static
	 * @access public
	 * @param $stream
	 * @return Socket
	 */
	public static function createFromStream($stream)
	{
		$socket = new self('localhost', 0);
		$socket->setStream($stream);
		return $socket;
	}

	// }}}
	// {{{ public function setStream()

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

	// }}}
	// {{{ public function connect()

	/**
	 * Connects the socket
	 *
	 * @access public
	 * @return void
	 */
	public function connect()
	{
		if ($this->stream instanceof \Swoole\Coroutine\Socket) {
			return;
		}

		if (empty($this->host)) {
			throw new \Kafka\Exception('Cannot open null host.');
		}
		if ($this->port <= 0) {
			throw new \Kafka\Exception('Cannot open without port.');
		}

		$this->stream = new \Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM);

		if (!$this->stream->connect($this->host, $this->port)) {
			$error = 'Could not connect to '
				. $this->host . ':' . $this->port
				. ' (' . $this->stream->errMsg . ' [' . $this->stream->errMsg . '])';
			throw new \Kafka\Exception($error);
		}

//		stream_set_blocking($this->stream, 0);
	}

	// }}}
	// {{{ public function close()

	/**
	 * close the socket
	 *
	 * @access public
	 * @return void
	 */
	public function close()
	{
		if ($this->stream instanceof \Swoole\Coroutine\Socket) {
			$this->stream->close();
		}
	}

	/**
	 * checks if the socket is a valid resource
	 *
	 * @access public
	 * @return boolean
	 */
	public function isResource()
	{
		return $this->stream instanceof \Swoole\Coroutine\Socket && $this->stream->checkLiveness();
	}

	// }}}
	// {{{ public function read()

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
	 * @throws \Kafka\Exception
	 */
	public function read($len, $verifyExactLength = false)
	{
		if ($len > self::READ_MAX_LEN) {
			throw new \Kafka\Exception('Could not read ' . $len . ' bytes from stream, length too longer.');
		}

		$null = null;
		$read = array($this->stream);
//		$readable = @stream_select($read, $null, $null, $this->recvTimeoutSec, $this->recvTimeoutUsec);
//		if ($readable > 0) {
		$remainingBytes = $len;
		$data = $chunk = '';
		while ($remainingBytes > 0) {
			$chunk = $this->stream->recv($remainingBytes);
			if ($chunk === false) {
				$this->close();
				throw new \Kafka\Exception('Could not read ' . $len . ' bytes from stream (no data)');
			}
			if (strlen($chunk) === 0) {
				continue; // attempt another read
			}
			$data .= $chunk;
			$remainingBytes -= strlen($chunk);
		}
		if ($len === $remainingBytes || ($verifyExactLength && $len !== strlen($data))) {
			// couldn't read anything at all OR reached EOF sooner than expected
			$this->close();
			throw new \Kafka\Exception('Read ' . strlen($data) . ' bytes instead of the requested ' . $len . ' bytes');
		}

		return $data;
//		}
//		if (false !== $readable) {
//			$res = stream_get_meta_data($this->stream);
//			if (!empty($res['timed_out'])) {
//				$this->close();
//				throw new \Kafka\Exception('Timed out reading ' . $len . ' bytes from stream');
//			}
//		}
//		$this->close();
//		throw new \Kafka\Exception('Could not read ' . $len . ' bytes from stream (not readable)');
	}

	// }}}
	// {{{ public function write()

	/**
	 * Write to the socket.
	 *
	 * @param string $buf The data to write
	 *
	 * @return integer
	 * @throws \Kafka\Exception
	 */
	public function write($buf)
	{
		$null = null;

		$failedWriteAttempts = 0;
		$written = 0;
		$buflen = strlen($buf);


		$event = Snowflake::app()->getEvent();
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'close']);

		while ($written < $buflen) {
			if ($buflen - $written > self::MAX_WRITE_BUFFER) {
				// write max buffer size
				$wrote = $this->stream->send(substr($buf, $written, self::MAX_WRITE_BUFFER), 1);
			} else {
				// write remaining buffer bytes to stream
				$wrote = $this->stream->send(substr($buf, $written), 1);
			}
			if ($wrote === -1 || $wrote === false) {
				throw new \Kafka\Exception\Socket('Could not write ' . strlen($buf) . ' bytes to stream, completed writing only ' . $written . ' bytes');
			} elseif ($wrote === 0) {
				// Increment the number of times we have failed
				$failedWriteAttempts++;
				if ($failedWriteAttempts > $this->maxWriteAttempts) {
					throw new \Kafka\Exception\Socket('After ' . $failedWriteAttempts . ' attempts could not write ' . strlen($buf) . ' bytes to stream, completed writing only ' . $written . ' bytes');
				}
			} else {
				// If we wrote something, reset our failed attempt counter
				$failedWriteAttempts = 0;
			}
			$written += $wrote;
		}
		return $written;
	}

	// }}}
	// {{{ public function rewind()

	/**
	 * Rewind the stream
	 *
	 * @return void
	 */
	public function rewind()
	{
//		if (is_resource($this->stream)) {
//			rewind($this->stream);
//		}
	}

	// }}}
	// }}}
}
