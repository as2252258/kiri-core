<?php

namespace Server\Constrict;

use Exception;
use HttpServer\Http\Formatter\FileFormatter;
use HttpServer\IInterface\IFormatter;
use Kiri\Exception\NotFindClassException;
use ReflectionException;
use Server\ResponseInterface;
use Swoole\Server;


/**
 *
 */
class ResponseEmitter
{


	/**
	 * @param \Swoole\Http\Response|\Swoole\Http2\Response|Server $response
	 * @param ResponseInterface $emitter
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function sender(\Swoole\Http\Response|\Swoole\Http2\Response|Server $response, ResponseInterface $emitter)
	{
		$content = $emitter->configure($response)->getContent();
		if ($response instanceof Server) {
			$this->sendTcpData($response, $emitter, $content);
			return;
		}
		if ($content instanceof FileFormatter) {
			$this->download($content->getData(), $response);
		} else {
			$response->header('Content-Type', $emitter->getResponseFormat());
			$response->end($content->getData());
		}
	}


	/**
	 * @param Server $response
	 * @param ResponseInterface $emitter
	 * @param IFormatter $formatter
	 */
	private function sendTcpData(Server $response, ResponseInterface $emitter, IFormatter $formatter)
	{
		if ($formatter instanceof FileFormatter) {
			$response->sendfile($emitter->getClientId(), $formatter->getData());
		} else {
			$response->send($emitter->getClientId(), $formatter->getData());
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
