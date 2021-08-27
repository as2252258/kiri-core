<?php

namespace Server\Constrict;


use Kiri\Exception\NotFindClassException;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class WebSocketEmitter implements Emitter
{


	/**
	 * @param mixed $response
	 * @param ResponseInterface $emitter
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
//		$response->push($emitter->getClientId(), $emitter->getContent()->getData());
	}
}
