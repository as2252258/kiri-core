<?php

namespace Kiri\Websocket\Contract;


use Swoole\WebSocket\Frame;

interface OnMessageInterface
{


	/**
	 * @param Frame $frame
	 * @return void
	 */
	public function OnMessage(Frame $frame): void;


}
