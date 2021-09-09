<?php


namespace Server\Constrict;


use Http\Context\Context;
use JetBrains\PhpStorm\Pure;
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
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $version
	 * @return ResponseInterface
	 */
	public function withProtocolVersion($version): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($version);
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name): string
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface
	 */
	public function withHeader($name, $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return ResponseInterface
	 */
	public function withAddedHeader($name, $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @return ResponseInterface
	 */
	public function withoutHeader($name): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @return StreamInterface
	 */
	public function getBody(): StreamInterface
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param StreamInterface $body
	 * @return ResponseInterface
	 */
	public function withBody(StreamInterface $body): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($body);
	}

	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return ResponseInterface
	 */
	public function withStatus($code, $reasonPhrase = ''): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($code, $reasonPhrase);
	}


	/**
	 * @return string
	 */
	public function getReasonPhrase(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}

	/**
	 * @param string $path
	 * @return DownloadInterface
	 */
	public function file(string $path): DownloadInterface
	{
		return $this->__call__()->{__FUNCTION__}($path);
	}

	/**
	 * @param $responseData
	 * @return string|array|bool|int|null
	 */
	public function _toArray($responseData): string|array|null|bool|int
	{
		return $this->__call__()->{__FUNCTION__}($responseData);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function xml($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function html($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @param $data
	 * @return ResponseInterface
	 */
	public function json($data): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($data);
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}

	/**
	 * @return bool
	 */
	public function hasContentType(): bool
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $type
	 * @return ResponseInterface
	 */
	public function withContentType(string $type): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($type);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowOrigin(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlRequestMethod(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @param string|null $value
	 * @return ResponseInterface
	 */
	public function withAccessControlAllowHeaders(?string $value): ResponseInterface
	{
		return $this->__call__()->{__FUNCTION__}($value);
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowOrigin(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlAllowHeaders(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getAccessControlRequestMethod(): ?string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @return array
	 */
	public function getClientInfo(): array
	{
		return $this->__call__()->{__FUNCTION__}();
	}
}
