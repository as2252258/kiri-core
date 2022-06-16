<?php

namespace Kiri\Websocket\Contract;


use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Swoole\Coroutine\Http\Server as CoroutineServer;

interface OnMessageInterface
{


	/**
	 * @param Server|CoroutineServer $server
	 * @param Frame $frame
	 * @return void
	 */
	public function onMessage(Server|CoroutineServer $server, Frame $frame): void;


}
