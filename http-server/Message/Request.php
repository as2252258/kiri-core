<?php

namespace Server\Message;

use Annotation\Inject;
use BadMethodCallException;
use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionException;


/**
 *
 */
class Request implements RequestInterface
{

	use Message;


	public string $requestTarget;


	public string $method;


	/**
	 * @var Uri
	 */
	private Uri $uri;


	private \Swoole\Http\Request $serverRequest;


	private array $parseBody;

	private array $files = [];


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->serverRequest->fd;
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return RequestInterface
	 */
	public static function parseRequest(\Swoole\Http\Request $request): RequestInterface
	{
		$message = new Request();
		$message->uri = Uri::parseUri($request);
		$message->method = $request->getMethod();
		$message->requestTarget = '';
		$message->serverRequest = $request;
		$message->version = $request->server['server_protocol'];
		$message->stream = new Stream($request->getContent());
		$message->servers = $request->server;
		$message->parseRequestHeaders($request);
		return $message;
	}


	/**
	 * @return float
	 */
	#[Pure] public function getStartTime(): float
	{
		return $this->servers['request_time_float'];
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function input($name, $default = null): mixed
	{
		if (empty($this->parseBody)) {
			$this->parseBody = $this->parseBody($this->stream);
		}
		if (!is_array($this->parseBody)) {
			return $default;
		}
		return $this->parseBody[$name] ?? $default;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function post($name, $default = null): mixed
	{
		return $this->serverRequest->post[$name] ?? $default;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function query($name, $default = null): mixed
	{
		return $this->serverRequest->get[$name] ?? $default;
	}


	/**
	 * @param $name
	 * @return Uploaded|null
	 */
	public function file($name): ?Uploaded
	{
		if (isset($this->serverRequest->files[$name])) {
			return new Uploaded($this->serverRequest->files[$name]);
		}
		return null;
	}


	/**
	 * @return string
	 */
	public function getRequestTarget(): string
	{
		throw new BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @param mixed $requestTarget
	 * @return RequestInterface
	 */
	public function withRequestTarget($requestTarget): RequestInterface
	{
		$class = clone $this;
		$class->requestTarget = $requestTarget;
		return $class;
	}


	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}


	/**
	 * @param string $method
	 * @return bool
	 */
	public function isMethod(string $method): bool
	{
		return $this->method === $method;
	}


	/**
	 * @param string $method
	 * @return RequestInterface
	 */
	public function withMethod($method): RequestInterface
	{
		$class = clone $this;
		$class->method = $method;
		return $class;
	}


	/**
	 * @return UriInterface
	 */
	public function getUri(): UriInterface
	{
		return $this->uri;
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return RequestInterface
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
	{
		$class = clone $this;
		$class->uri = $uri;
		return $class;
	}
}
