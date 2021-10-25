<?php

namespace Http\Message;

use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Core\Help;
use Http\Constrict\ResponseInterface;
use Http\OnDownloadInterface;

/**
 *
 */
class Response implements ResponseInterface
{


	use Message;


	const CONTENT_TYPE_HTML = 'text/html';


    protected string $charset = 'utf8';


	protected int $statusCode = 200;


	protected string $reasonPhrase = '';


	/**
	 * __construct
	 */
	public function __construct()
	{
		$this->stream = new Stream();
	}


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
	 * @param string $contentType
	 * @return static
	 */
	public function json($data, string $contentType = 'application/json'): static
	{
		$this->stream->write(json_encode($data));

		return $this->withContentType($contentType);
	}


	/**
	 * @param $data
	 * @param string $contentType
	 * @return static
	 */
	public function html($data, string $contentType = 'text/html'): static
	{
		if (!is_string($data)) {
			$data = json_encode($data);
		}

		$this->stream->write((string)$data);

		return $this->withContentType($contentType);
	}


	/**
	 * @param $data
	 * @param string $contentType
	 * @return static
	 */
	public function xml($data, string $contentType = 'application/xml'): static
	{
		$this->stream->write(Help::toXml($data));

		return $this->withContentType($contentType);
	}


    /**
     * @param string $charset
     * @return $this
     */
    public function withCharset(string $charset): static
    {
        $type = explode('charset', $this->getContentType())[0];
        $this->withContentType(
            rtrim($type,';') . ';charset=' . $charset
        );
        return $this;
    }


	/**
	 * @param $path
	 * @param bool $isChunk
	 * @param int $size
	 * @param int $offset
	 * @return OnDownloadInterface
	 * @throws Exception
	 */
	public function file($path, bool $isChunk = false, int $size = -1, int $offset = 0): OnDownloadInterface
	{
		$path = realpath($path);
		if (!file_exists($path) || !is_readable($path)) {
			throw new Exception('Cannot read file "' . $path . '", no permission');
		}
		return (new OnDownload())->path($path, $isChunk, $size, $offset);
	}
}
