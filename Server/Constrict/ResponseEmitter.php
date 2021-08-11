<?php

namespace Server\Constrict;

use Exception;
use HttpServer\Http\Formatter\FileFormatter;
use ReflectionException;
use Server\ResponseInterface;
use Kiri\Exception\NotFindClassException;


/**
 *
 */
class ResponseEmitter
{


	/**
	 * @param \Swoole\Http\Response|\Swoole\Http2\Response $response
	 * @param ResponseInterface $emitter
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function sender(\Swoole\Http\Response|\Swoole\Http2\Response $response, ResponseInterface $emitter)
	{
		$response->header('Content-Type', $emitter->getResponseFormat());
		$response->end('$content->getData()');
		return;
		$content = $emitter->configure($response)->getContent();
		if ($content instanceof FileFormatter) {
			$this->download($content->getData(), $response);
		} else {
			$response->header('Content-Type', $emitter->getResponseFormat());
			$response->end($content->getData());

			var_dump($response->isWritable());
		}
	}


	const IMAGES = [
		'png'  => 'image/png',
		'jpeg' => 'image/jpeg',
		'gif'  => 'image/gif',
		'bmp'  => 'image/bmp',
		'ico'  => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'svg'  => 'image/svg+xml',
	];


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
		$response->header('Content-Disposition', 'attachment;filename=' . end($explode));
		$response->header('Content-Type', $type = get_file_extension($content['path']));
		if (!in_array($type, self::IMAGES)) {
			$response->header('Content-Transfer-Encoding', 'binary');
		} else {
			$response->end(file_get_contents($content['path']));
			return;
		}
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

		$offset = $content['offset'];

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
