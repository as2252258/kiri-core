<?php

namespace Server\Constrict;


use Kiri\Exception\NotFindClassException;
use Server\ResponseInterface;
use Swoole\WebSocket\Server;

/**
 *
 */
class WebSocketEmitter implements Emitter
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
		$response->push($emitter->getClientId(), $emitter->getContent()->getData());
	}
}
