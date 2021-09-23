<?php

namespace Server\SInterface;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface OnRequestInterface
{


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onRequest(Request $request, Response $response): void;

}
