<?php

namespace Server\Constrict;


use Kiri\Exception\NotFindClassException;
use ReflectionException;
use Server\ResponseInterface;

/**
 *
 */
class DownloadEmitter implements Emitter
{

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
	 * @param mixed $response
	 * @param ResponseInterface $emitter
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function sender(mixed $response, ResponseInterface $emitter): void
	{
		$content = $emitter->getContent()->getData();

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
