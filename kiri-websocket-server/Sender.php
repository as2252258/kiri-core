<?php

namespace Kiri\Websocket;

use Kiri\Kiri;
use Swoole\{Coroutine\Http\Server as AliasServer, WebSocket\Server};


/**
 *
 */
class Sender implements WebSocketInterface
{


	/**
	 * @var AliasServer|Server
	 */
	private AliasServer|Server $server;


	/**
	 *
	 */
	public function __construct()
	{
		$this->server = Kiri::getDi()->get(WebSocketInterface::class);
	}


	/**
	 * @param int $fd
	 * @param mixed $data
	 * @param int $opcode
	 * @param int $flags
	 * @return bool
	 */
	public function push(int $fd, string $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flags = SWOOLE_WEBSOCKET_FLAG_FIN): bool
	{
		if ($this->isEstablished($fd)) {
			return $this->server->push($fd, $data, $opcode, $flags);
		}
		return false;
	}


	/**
	 * @param $fd
	 * @param $reactor_id
	 * @return array|null
	 */
	public function connection_info($fd, $reactor_id = null): ?array
	{
		return $this->server->getClientInfo($fd, $reactor_id);
	}


	/**
	 * @param int $fd
	 * @param int $code
	 * @param string $reason
	 * @return bool
	 */
	public function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
	{
		if ($this->isEstablished($fd)) {
			return $this->server->disconnect($fd, $code, $reason);
		}
		return false;
	}


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function isEstablished(int $fd): bool
	{
		return $this->exist($fd) && $this->server->isEstablished($fd);
	}


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function exist(int $fd): bool
	{
		return $this->server->exist($fd);
	}

}
