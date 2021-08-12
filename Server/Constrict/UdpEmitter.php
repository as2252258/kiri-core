<?php

namespace Server\Constrict;

use Kiri\Exception\NotFindClassException;
use Server\ResponseInterface;
use Swoole\Server;


/**
 *
 */
class UdpEmitter implements Emitter
{


	/**
	 * @param Server $response
	 * @param ResponseInterface $emitter
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function sender(Server $response, ResponseInterface $emitter)
	{
		$clientInfo = $emitter->getClientInfo();
		$response->sendto($clientInfo['host'], $clientInfo['port'],
			$emitter->getContent()->getData()
		);
	}
}
