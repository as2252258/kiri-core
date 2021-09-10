<?php

namespace Protocol\Message;

use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Core\Help;
use Psr\Http\Message\ResponseInterface;
use Server\SInterface\DownloadInterface;


class Response implements ResponseInterface
{


	use Message;


	const CONTENT_TYPE_JSON = 'application/json;charset=utf-8';
	const CONTENT_TYPE_HTML = 'text/html;charset=utf-8';
	const CONTENT_TYPE_STREAM = 'octet-stream';
	const CONTENT_TYPE_XML = 'application/xml;charset=utf-8';


	protected int $statusCode = 200;


	protected string $reasonPhrase = '';


	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}


	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return $this|Response
	 */
	public function withStatus($code, $reasonPhrase = ''): static
	{
		$this->statusCode = $code;
		$this->reasonPhrase = $reasonPhrase;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		return $this->reasonPhrase;
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowOrigin(): ?string
	{
		return $this->getHeaderLine('Access-Control-Allow-Origin');
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowHeaders(): ?string
	{
		return $this->getHeaderLine('Access-Control-Allow-Headers');
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlRequestMethod(): ?string
	{
		return $this->getHeaderLine('Access-Control-Request-Method');
	}


	/**
	 * @param string $type
	 * @return Response
	 */
	public function withContentType(string $type): static
	{
		return $this->withHeader('Content-Type', $type);
	}


	/**
	 * @return bool
	 */
	#[Pure] public function hasContentType(): bool
	{
		return $this->hasHeader('Content-Type');
	}


	/**
	 * @return string
	 */
	#[Pure] public function getContentType(): string
	{
		return $this->getHeaderLine('Content-Type');
	}

	/**
	 * @param string|null $value
	 * @return Response
	 */
	public function withAccessControlAllowHeaders(?string $value): static
	{
		return $this->withHeader('Access-Control-Allow-Headers', $value);
	}


	/**
	 * @param string|null $value
	 * @return Response
	 */
	public function withAccessControlRequestMethod(?string $value): static
	{
		return $this->withHeader('Access-Control-Request-Method', $value);
	}


	/**
	 * @param string|null $value
	 * @return Response
	 */
	public function withAccessControlAllowOrigin(?string $value): static
	{
		return $this->withHeader('Access-Control-Allow-Origin', $value);
	}


	/**
	 * @param $data
	 * @return static
	 * @throws Exception
	 */
	public function json($data): static
	{
		$this->stream->write(json_encode($data));

		return $this->withContentType(self::CONTENT_TYPE_JSON);
	}


	/**
	 * @param $data
	 * @return static
	 * @throws Exception
	 */
	public function html($data): static
	{
		if (!is_string($data)) {
			$data = json_encode($data);
		}

		$this->stream->write((string)$data);

		return $this->withContentType(self::CONTENT_TYPE_HTML);
	}


	/**
	 * @param $data
	 * @return static
	 * @throws Exception
	 */
	public function xml($data): static
	{

		$this->stream->write(Help::toXml($data));

		return $this->withContentType(self::CONTENT_TYPE_XML);
	}


	/**
	 * @param $path
	 * @param bool $isChunk
	 * @param int $size
	 * @param int $offset
	 * @return DownloadInterface
	 * @throws Exception
	 */
	public function file($path, bool $isChunk = false, int $size = -1, int $offset = 0): DownloadInterface
	{
		$path = realpath($path);
		if (!file_exists($path) || !is_readable($path)) {
			throw new Exception('Cannot read file "' . $path . '", no permission');
		}
		return (new Download())->path($path, $isChunk, $size, $offset);
	}
}
