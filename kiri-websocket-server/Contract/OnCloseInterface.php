<?php

namespace Kiri\Websocket\Contract;


use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Swoole\Coroutine\Http\Server as CoroutineServer;

interface OnCloseInterface
{


	/**
	 * @param Server|CoroutineServer $server
	 * @param int $fd
	 * @return void
	 */
	public function onClose(Server|CoroutineServer $server, int $fd): void;


}
