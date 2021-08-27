<?php

namespace Server\Constrict;

use Kiri\Exception\NotFindClassException;
use Psr\Http\Message\ResponseInterface;
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
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
		$clientInfo = $emitter->getClientInfo();
		$response->sendto($clientInfo['host'], $clientInfo['port'],
			$emitter->getContent()->getData()
		);
	}
}
