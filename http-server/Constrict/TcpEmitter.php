<?php

namespace Server\Constrict;

use Exception;
use Http\Handler\Formatter\FileFormatter;
use Swoole\Server;


/**
 *
 */
class TcpEmitter implements Emitter
{


	/**
	 * @param Server $response
	 * @param ResponseInterface $emitter
	 * @throws Exception
	 */
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
		$formatter = $emitter->stream->getContents();
		if ($formatter instanceof FileFormatter) {
			$response->sendfile($emitter->getClientId(), $formatter);
		} else {
			$response->send($emitter->getClientId(), $formatter);
		}
	}
}
