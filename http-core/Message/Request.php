<?php

namespace Http\Message;

use BadMethodCallException;
use Http\Handler\AuthIdentity;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;


/**
 *
 */
class Request implements RequestInterface
{

	use Message;


	/**
	 * @var UriInterface
	 */
	protected UriInterface $uriInterface;


	/**
	 * @var string
	 */
	protected string $method;


	/**
	 * @var AuthIdentity|null
	 */
	public ?AuthIdentity $authority = null;


	/**
	 * @param AuthIdentity|null $authIdentity
	 */
	public function setAuthority(?AuthIdentity $authIdentity): void
	{
		$this->authority = $authIdentity;
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
	 * @return static
	 */
	public function withRequestTarget($requestTarget): static
	{
		throw new BadMethodCallException('Not Accomplish Method.');
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
	 * @return RequestInterface
	 */
	public function withMethod($method): RequestInterface
	{
		$this->method = $method;
		return $this;
	}


	/**
	 * @param string $method
	 * @return bool
	 */
	public function isMethod(string $method): bool
	{
		return $this->method == $method;
	}


	/**
	 * @return UriInterface
	 */
	public function getUri(): UriInterface
	{
		return $this->uriInterface;
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return $this|Request
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
	{
		$this->uriInterface = $uri;
		return $this;
	}
}
