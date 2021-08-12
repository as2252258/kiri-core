<?php

namespace Server\Constrict;

use Exception;
use HttpServer\Http\Formatter\FileFormatter;
use Kiri\Exception\NotFindClassException;
use ReflectionException;
use Server\ResponseInterface;
use Swoole\Server;


/**
 *
 */
class ResponseEmitter implements Emitter
{


	/**
	 * @param \Swoole\Http\Response|\Swoole\Http2\Response $response
	 * @param ResponseInterface $emitter
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
		$content = $emitter->configure($response)->getContent();
		if ($content instanceof FileFormatter) {
			di(DownloadEmitter::class)->sender($response, $emitter);
			return;
		}
		$response->header('Content-Type', $emitter->getResponseFormat());
		$response->end($content->getData());
	}

}
