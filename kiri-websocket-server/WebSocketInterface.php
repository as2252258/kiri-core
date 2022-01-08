<?php

namespace Kiri\Websocket;


/**
 * @mixin \Swoole\WebSocket\Server
 * @mixin \Swoole\Coroutine\Http\Server
 */
interface WebSocketInterface
{


	/**
	 * @param int $fd
	 * @param mixed $data
	 * @param int $opcode
	 * @param int $flags
	 * @return bool
	 */
	public function push(int $fd, string $data, int $opcode = WEBSOCKET_OPCODE_TEXT, int $flags = SWOOLE_WEBSOCKET_FLAG_FIN): bool;


	/**
	 * @param int $fd
	 * @param int $code
	 * @param string $reason
	 * @return mixed
	 */
	public function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''): bool;


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function isEstablished(int $fd): bool;


	/**
	 * @param int $fd
	 * @return bool
	 */
	public function exist(int $fd): bool;


}
