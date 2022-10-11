<?php

namespace Kiri\Websocket\Contract;


use Swoole\Http\Request;

interface OnOpenInterface
{


	/**
	 * @param Request $request
	 * @return void
	 */
	public function onOpen(Request $request): void;


}
