<?php

namespace Server\Service;



use Server\Abstracts\Server;


/**
 *
 */
class Udp extends \Server\Abstracts\Udp
{


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 */
	public function onPacket(Server $server, string $data, array $clientInfo): void
	{
		// TODO: Implement onPacket() method.
	}

}
