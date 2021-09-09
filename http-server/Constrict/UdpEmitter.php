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
	 * @throws \Exception
	 */
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
		$clientInfo = $emitter->getClientInfo();
		$response->sendto($clientInfo['host'], $clientInfo['port'],
			$emitter->stream->getContents()
		);
	}
}
