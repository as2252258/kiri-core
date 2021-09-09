<?php


namespace Server\Constrict;


use Http\Context\Context;
use Psr\Http\Message\StreamInterface;
use Server\Message\Response as Psr7Response;
use Server\ResponseInterface;
use Server\SInterface\DownloadInterface;


/**
 * Class Response
 * @package Server
 */
class Response implements ResponseInterface
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';
	const FILE = 'file';


	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return $this->__call__()->{$name};
	}


	/**
	 * @return Psr7Response
	 */
	public function __call__(): Psr7Response
	{
		return Context::getContext(ResponseInterface::class, new Psr7Response());
	}


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->__call__()->{__METHOD__}();
	}


	/**
	 * @param string $version
	 * @return ResponseInterface
	 */
	public function withProtocolVersion($version): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($version);
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->__call__()->{__METHOD__}();
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return $this->__call__()->{__METHOD__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name): string
	{
		return $this->__call__()->{__METHOD__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		return $this->__call__()->{__METHOD__}($name);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface
	 */
	public function withHeader($name, $value): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($name, $value);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface
	 */
	public function withAddedHeader($name, $value): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($name, $value);
	}


	/**
	 * @param string $name
	 * @return ResponseInterface
	 */
	public function withoutHeader($name): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($name);
	}


	/**
	 * @return StreamInterface
	 */
	public function getBody(): StreamInterface
	{
		return $this->__call__()->{__METHOD__}();
	}


	/**
	 * @param StreamInterface $body
	 * @return ResponseInterface
	 */
	public function withBody(StreamInterface $body): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($body);
	}

	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->__call__()->{__METHOD__}();
	}


	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return ResponseInterface
	 */
	public function withStatus($code, $reasonPhrase = ''): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($code, $reasonPhrase);
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		return $this->__call__()->{__METHOD__}();
	}

	/**
	 * @param string $path
	 * @return DownloadInterface
	 */
	public function file(string $path): DownloadInterface
	{
		return $this->__call__()->{__METHOD__}($path);
	}

	/**
	 * @param $responseData
	 * @return string|array|bool|int|null
	 */
	public function _toArray($responseData): string|array|null|bool|int
	{
		return $this->__call__()->{__METHOD__}($responseData);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function xml($data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function html($data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function json($data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->__call__()->{__METHOD__}();
	}

	/**
	 * @return bool
	 */
	public function hasContentType(): bool
	{
		return $this->__call__()->{__METHOD__}();
	}


	/**
	 * @param string $type
	 * @return ResponseInterface
	 */
	public function withContentType(string $type): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($type);
	}


	/**
	 * @param string $data
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin(string $data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}


	/**
	 * @param string $data
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod(string $data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}


	/**
	 * @param string $data
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders(string $data): ResponseInterface
	{
		return $this->__call__()->{__METHOD__}($data);
	}
}
