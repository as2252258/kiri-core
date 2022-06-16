<?php

namespace Kiri\Websocket\Contract;


use Swoole\Http\Request;
use Swoole\Http\Response;

interface OnHandshakeInterface
{


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return int
	 */
	public function onHandshake(Request $request, Response $response): int;


}
