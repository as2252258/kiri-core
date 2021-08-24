<?php

namespace Server\SInterface;

use Server\Abstracts\Server;

interface OnPacket
{


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 * @return mixed
	 */
	public function onPacket(Server $server, string $data, array $clientInfo): void;

}
