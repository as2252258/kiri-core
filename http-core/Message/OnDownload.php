<?php

namespace Http\Message;

use Http\OnDownloadInterface;

class OnDownload extends Response implements OnDownloadInterface
{

	use Message;


	private string $path;
	private bool $isChunk;
	private int $size;
	private int $offset;

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
	 * @param string $path
	 * @param false $isChunk
	 * @param int $size
	 * @param int $offset
	 * @return $this
	 */
	public function path(string $path, bool $isChunk = false, int $size = -1, int $offset = 0): OnDownload
	{
		$this->path = $path;
		$this->isChunk = $isChunk;
		$this->size = $size;
		$this->offset = $offset;
		return $this->emitter();
	}


	/**
	 * @return $this
	 */
	public function emitter(): static
	{
		$explode = explode('/', $this->path);
		$this->withHeader('Pragma', 'public');
		$this->withHeader('Expires', '0');
		$this->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
		$this->withHeader('Content-Disposition', 'attachment;filename=' . end($explode));
		$this->withHeader('Content-Type', $type = get_file_extension($this->path));
		if (!in_array($type, self::IMAGES)) {
			$this->withHeader('Content-Transfer-Encoding', 'binary');
		}
		if ($this->isChunk) {
			$resource = fopen($this->path, 'r');

			$state = fstat($resource);

			$this->withHeader('Content-length', $state['size']);
		}
		return $this;
	}


	/**
	 * @param \Swoole\Http\Response $response
	 */
	public function dispatch(mixed $response)
	{
		if (!$this->isChunk) {
			$response->sendfile($this->path);
		} else {
			$this->chunk($response);
		}
	}


	/**
	 * @param \Swoole\Http\Response $response
	 */
	private function chunk(\Swoole\Http\Response $response): void
	{
		$resource = fopen($this->path, 'r');

		$state = fstat($resource);

		$offset = $this->offset;
		while ($file = fread($resource, $this->size)) {
			$response->write($file);
			fseek($resource, $offset);
			if ($offset >= $state['size']) {
				break;
			}
			$offset += $this->size;
		}
		$response->end();
	}
}
