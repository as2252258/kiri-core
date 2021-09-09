<?php

namespace Server\Constrict;

use Http\Context\Context;
use Http\IInterface\AuthIdentity;
use JetBrains\PhpStorm\Pure;
use Kiri\Kiri;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Server\Message\Request as RequestMessage;
use Server\Message\Response;
use Server\Message\Uploaded;
use Server\RequestInterface;
use Server\ResponseInterface;


class Request implements RequestInterface
{


	/**
	 * @return RequestMessage
	 */
	private function __call__(): RequestMessage
	{
		return Context::getContext(RequestMessage::class, new RequestMessage());
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name): mixed
	{
		// TODO: Change the autogenerated stub
		return $this->__call__()->{$name};
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return array<RequestInterface, ResponseInterface>
	 */
	public static function create(\Swoole\Http\Request $request): array
	{
		Context::setContext(ResponseInterface::class, $response = new Response());

		Context::setContext(RequestMessage::class, RequestMessage::parseRequest($request));

		return [Kiri::getDi()->get(Request::class), $response];
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
	 * @return Request
	 */
	public function withProtocolVersion($version): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($version);
	}


	/**
	 * @return \string[][]
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
	 * @return string[]
	 */
	public function getHeader($name): array
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
	 * @return Request
	 */
	public function withHeader($name, $value): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return Request
	 */
	public function withAddedHeader($name, $value): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($name, $value);
	}


	/**
	 * @param string $name
	 * @return Request
	 */
	public function withoutHeader($name): RequestInterface
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
	 * @return Request
	 */
	public function withBody(StreamInterface $body): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($body);
	}


	/**
	 * @return string
	 */
	public function getRequestTarget(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param mixed $requestTarget
	 * @return Request
	 */
	public function withRequestTarget($requestTarget): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($requestTarget);
	}


	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $method
	 * @return bool
	 */
	public function isMethod(string $method): bool
	{
		return $this->__call__()->{__FUNCTION__}($method);
	}


	/**
	 * @param string $method
	 * @return Request
	 */
	public function withMethod($method): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($method);
	}


	/**
	 * @return UriInterface
	 */
	public function getUri(): UriInterface
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return Request
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
	{
		return $this->__call__()->{__FUNCTION__}($uri, $preserveHost);
	}


	/**
	 * @param string $name
	 * @return Uploaded|null
	 */
	public function file(string $name): ?Uploaded
	{
		return $this->__call__()->{__FUNCTION__}($name);
	}


	/**
	 * @return array
	 */
	public function all(): array
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $name
	 * @param bool|int|string|null $default
	 * @return mixed
	 */
	public function query(string $name, bool|int|string|null $default = null): mixed
	{
		return $this->__call__()->{__FUNCTION__}($name, $default);
	}


	/**
	 * @param string $name
	 * @param int|bool|array|string|null $default
	 * @return mixed
	 */
	public function post(string $name, int|bool|array|string|null $default = null): mixed
	{
		return $this->__call__()->{__FUNCTION__}($name, $default);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return int|null
	 */
	public function int(string $name, bool $required = false): ?int
	{
		return $this->__call__()->{__FUNCTION__}($name, $required);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return float|null
	 */
	public function float(string $name, bool $required = false): ?float
	{
		return $this->__call__()->{__FUNCTION__}($name, $required);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return string|null
	 */
	public function date(string $name, bool $required = false): ?string
	{
		return $this->__call__()->{__FUNCTION__}($name, $required);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return int|null
	 */
	public function timestamp(string $name, bool $required = false): ?int
	{
		return $this->__call__()->{__FUNCTION__}($name, $required);
	}


	/**
	 * @param string $name
	 * @param bool $required
	 * @return string|null
	 */
	public function string(string $name, bool $required = false): ?string
	{
		return $this->__call__()->{__FUNCTION__}($name, $required);
	}


	/**
	 * @param string $name
	 * @param array $default
	 * @return array|null
	 */
	public function array(string $name, array $default = []): ?array
	{
		return $this->__call__()->{__FUNCTION__}($name, $default);
	}


	/**
	 * @return array|null
	 */
	public function gets(): ?array
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param string $field
	 * @param string $sizeField
	 * @param int $max
	 * @return float|int
	 */
	public function offset(string $field = 'page', string $sizeField = 'size', int $max = 100): float|int
	{
		return $this->__call__()->{__FUNCTION__}($field, $sizeField, $max);
	}


	/**
	 * @param string $field
	 * @param int $max
	 * @return int
	 */
	public function size(string $field = 'size', int $max = 100): int
	{
		return $this->__call__()->{__FUNCTION__}($field, $max);
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function input($name, $default = null): mixed
	{
		return $this->__call__()->{__FUNCTION__}($name, $default);
	}


	/**
	 * @return float
	 */
	#[Pure] public function getStartTime(): float
	{
		return $this->__call__()->{__FUNCTION__}();
	}


	/**
	 * @param AuthIdentity $authority
	 */
	public function setAuthority(AuthIdentity $authority): void
	{
		$this->__call__()->{__FUNCTION__}($authority);
	}


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->__call__()->{__FUNCTION__}();
	}
}
