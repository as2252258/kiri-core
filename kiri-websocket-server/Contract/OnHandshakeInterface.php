<?php

namespace Kiri\Websocket\Contract;


use Swoole\Http\Request;
use Swoole\Http\Response;

interface OnHandshakeInterface
{


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function OnHandshake(Request $request, Response $response): void;


}
