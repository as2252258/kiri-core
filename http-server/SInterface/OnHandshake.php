<?php

namespace Server\SInterface;

use Swoole\Http\Request;
use Swoole\Http\Response;


/**
 *
 */
interface OnHandshake
{


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onHandshake(Request $request, Response $response): void;

}
