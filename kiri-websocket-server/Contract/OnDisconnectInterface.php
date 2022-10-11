<?php

namespace Kiri\Websocket\Contract;

interface OnDisconnectInterface
{


	/**
	 * @param int $fd
	 */
    public function OnDisconnect(int $fd): void;


}
