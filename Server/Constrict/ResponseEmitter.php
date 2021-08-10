<?php

namespace Server\Constrict;

use Exception;
use HttpServer\Http\Formatter\FileFormatter;
use Server\ResponseInterface;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;


/**
 *
 */
class ResponseEmitter
{


	/**
	 * @param \Swoole\Http\Response $response
	 * @param ResponseInterface $emitter
	 * @throws Exception
	 */
	public function sender(\Swoole\Http\Response $response, ResponseInterface $emitter)
	{
		$content = $emitter->configure($response)->getContent();
		if (!($content instanceof FileFormatter)) {
			$response->end($content->getData());
			return;
		}
		$this->download($content->getData(), $response);
	}


	/**
	 * @param array $content
	 * @param \Swoole\Http\Response $response
	 */
	private function download(array $content, \Swoole\Http\Response $response)
	{
		if ($content['isChunk'] === false) {
			$response->sendfile($content['path']);
			return;
		}

		$resource = fopen($content['path'], 'r');

		$state = fstat($resource);

		$offset = 0;

		$response->header('Content-length', $state['size']);
		while ($file = fread($resource, $content['limit'])) {
			$response->write($file);
			fseek($resource, $offset);
			if ($offset >= $state['size']) {
				break;
			}
			$offset += $content['limit'];
		}
		$response->end();
	}


}
