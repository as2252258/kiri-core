<?php

namespace Kiri\Websocket;

use Kiri;
use Swoole\{Coroutine\Http\Server as AliasServer, WebSocket\Server};


/**
 *
 */
class Sender implements WebSocketInterface
{


	/**
	 * @var AliasServer|Server|null
	 */
	private AliasServer|Server|null $server = null;


	/**
	 * @param AliasServer|Server $server
	 */
	public function setServer(mixed $server): void
	{
		$this->server = $server;
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
		if (!$this->isEstablished($fd)) {
			return false;
		}
		if ($this->server instanceof Server) {
			return $this->server->push($fd, $data, $opcode, $flags);
		}
		$collector = Kiri::getContainer()->get(FdCollector::class);

		$response = $collector->get($fd);
		if (!empty($response)) {
			return $response->push($data, $opcode, $flags);
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
		if ($this->server instanceof Server) {
			return $this->server->getClientInfo($fd, $reactor_id);
		}
		if ($this->server->exist($fd)) {
			return ['websocket_status' => 1];
		}
		return null;
	}


	/**
	 * @param int $fd
	 * @param int $code
	 * @param string $reason
	 * @return bool
	 */
	public function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool
	{
		if (!$this->isEstablished($fd)) {
			return false;
		}
		if ($this->server instanceof Server) {
			return $this->server->disconnect($fd, $code, $reason);
		}
		return $this->server->close($fd, $reason);
	}


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function isEstablished(int $fd): bool
	{
		if (!$this->exist($fd)) {
			return false;
		}

		if ($this->server instanceof Server) {
			return $this->server->isEstablished($fd);
		}
		return true;
	}


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function exist(int $fd): bool
	{
		if ($this->server instanceof Server) {
			return $this->server->exist($fd);
		}
		$collector = Kiri::getContainer()->get(FdCollector::class);
		return $collector->has($fd);
	}

}
