<?php

namespace Server\Constrict;


use Exception;
use Kiri\Kiri;
use Server\ServerManager;
use Server\Constrict\ResponseInterface;

/**
 *
 */
class WebSocketEmitter implements Emitter
{


	/**
	 * @param mixed $response
	 * @param ResponseInterface|\Http\Message\Response $emitter
	 * @throws Exception
	 */
	public function sender(mixed $response, ResponseInterface|\Http\Message\Response $emitter): void
	{
		$server = Kiri::getDi()->get(ServerManager::class)->getServer();

		$server->push($response->fd, $emitter->getBody()->getContents());
	}
}
