<?php

namespace Server\Constrict;

use Exception;
use HttpServer\Http\Formatter\FileFormatter;
use Server\ResponseInterface;
use validator\EnumValidator;


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
		if ($content instanceof FileFormatter) {
			$this->download($content->getData(), $response);
		} else {
			$response->end($content->getData());
		}
	}


	/**
	 * @param array $content
	 * @param \Swoole\Http\Response $response
	 */
	private function download(array $content, \Swoole\Http\Response $response)
	{
		$explode = explode('/', $content['path']);

		$response->header('Pragma', 'public');
		$response->header('Expires', '0');
		$response->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
//		$response->header('Content-Type', 'application/force-download');
		$response->header('Content-Type', 'application/octet-stream');
//		$response->header('Content-Type', 'application/vnd.ms-excel');
//		$response->header('Content-Type', 'application/download');
		$response->header('Content-Disposition', 'attachment;filename=' . end($explode));
		$response->header('Content-Transfer-Encoding', 'binary');

		if ($content['isChunk'] === false) {
			$response->sendfile($content['path']);
		} else {
			$this->chunk($content, $response);
		}
	}


	/**
	 * @param $content
	 * @param $response
	 */
	private function chunk($content, $response): void
	{
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
