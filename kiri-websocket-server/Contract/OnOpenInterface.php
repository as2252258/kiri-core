<?php

namespace Kiri\Websocket\Contract;


use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Swoole\Coroutine\Http\Server as CoroutineServer;

interface OnOpenInterface
{


	/**
	 * @param Server|CoroutineServer $server
	 * @param Request $request
	 * @return void
	 */
	public function onOpen(Server|CoroutineServer $server, Request $request): void;


}
