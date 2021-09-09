<?php

namespace Server\Constrict;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Server\Message\Message;

class TRequest implements RequestInterface
{

	use Message;


	private UriInterface $uri;


	private string $method;


	/**
	 * @return string
	 */
	public function getRequestTarget(): string
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @param mixed $requestTarget
	 * @return static
	 */
	public function withRequestTarget($requestTarget): static
	{
		throw new \BadMethodCallException('Not Accomplish Method.');
	}


	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		// TODO: Implement getMethod() method.
		return $this->method;
	}


	/**
	 * @param string $method
	 * @return RequestInterface
	 */
	public function withMethod($method): RequestInterface
	{
		// TODO: Implement withMethod() method.
		$this->method = $method;
		return $this;
	}


	/**
	 * @return UriInterface
	 */
	public function getUri(): UriInterface
	{
		// TODO: Implement getUri() method.
		return $this->uri;
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return $this|TRequest
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
	{
		$this->uri = $uri;
		return $this;
	}
}
