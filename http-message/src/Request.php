<?php

namespace HttpMessage;

use Annotation\Inject;
use JetBrains\PhpStorm\Pure;
use Kiri\Di\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;


/**
 *
 */
class Request implements RequestInterface
{

	use Message;


	public string $method;


	public mixed $requestTarget;


	/**
	 * @var UriInterface
	 */
	#[Inject(UriInterface::class)]
	public UriInterface $uri;


	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(public ContainerInterface $container)
	{
	}


	/**
	 * @return mixed
	 */
	public function getRequestTarget(): mixed
	{
		// TODO: Implement getRequestTarget() method.
		return $this->requestTarget;
	}


	/**
	 * @param mixed $requestTarget
	 * @return Request
	 */
	public function withRequestTarget($requestTarget): static
	{
		// TODO: Implement withRequestTarget() method.
		$newInstance = clone $this;
		$newInstance->requestTarget = $requestTarget;
		return $newInstance;
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
	 * @return $this
	 */
	public function withMethod($method): static
	{
		// TODO: Implement withMethod() method.
		$newInstance = clone $this;
		$newInstance->method = $method;
		return $newInstance;
	}


	/**
	 * @return string
	 */
	#[Pure] public function getUri(): string
	{
		// TODO: Implement getUri() method.
		$uri = $this->uri->getPath();
		if (!empty($this->uri->getQuery())) {
			$uri .= '?' . $this->uri->getQuery();
		}
		return $uri;
	}


	/**
	 * @param UriInterface $uri
	 * @param false $preserveHost
	 * @return Request
	 */
	public function withUri(UriInterface $uri, $preserveHost = false): static
	{
		// TODO: Implement withUri() method.
		$newInstance = clone $this;
		$newInstance->uri = $uri;
		return $newInstance;
	}
}
