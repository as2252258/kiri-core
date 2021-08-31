<?php

namespace Server;

use Annotation\Inject;
use Server\Constrict\Response;
use Server\Message\Stream;

class Sender
{


	/**
	 * @var Response
	 */
	#[Inject(Response::class)]
	public Response $response;


	/**
	 * @var ServerManager
	 */
	#[Inject(ServerManager::class)]
	public ServerManager $manager;


	/**
	 * @param $fd
	 * @param $data
	 */
	public function send($fd, $data)
	{
		$body = $this->response->withBody(new Stream($data));
		$server = $this->manager->getServer();
		if (!$server->isEstablished($fd)) {
			return;
		}
		$server->push($fd, $body->getBody());
	}

}
