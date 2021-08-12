<?php

namespace Server\Constrict;

use HttpServer\Http\Formatter\FileFormatter;
use Kiri\Exception\NotFindClassException;
use Server\ResponseInterface;
use Swoole\Server;


/**
 *
 */
class TcpEmitter implements Emitter
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
		$formatter = $emitter->getContent();
		if ($formatter instanceof FileFormatter) {
			$response->sendfile($emitter->getClientId(), $formatter->getData());
		} else {
			$response->send($emitter->getClientId(), $formatter->getData());
		}
	}
}
