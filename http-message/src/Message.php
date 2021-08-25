<?php

namespace HttpMessage;

use Http\Context\Context;
use Kiri\Di\ContainerInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Server\RequestInterface;


/**
 *
 */
trait Message
{


	/**
	 * @var string
	 */
	public string $protocol = '1.1';


	/**
	 * @var mixed|string
	 */
	public mixed $body = '';


	/**
	 * @var array<string,array>
	 */
	public array $headers = [];


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->protocol;
	}

	/**
	 * @param string $version
	 * @return $this
	 */
	public function withProtocolVersion($version): static
	{
		// TODO: Implement withProtocolVersion() method.
		$new = clone $this;
		$new->protocol = $version;
		return $new;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		// TODO: Implement getHeaders() method.
		return $this->headers;
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		// TODO: Implement hasHeader() method.
		return isset($this->headers[$name]);
	}


	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getHeader($name): mixed
	{
		// TODO: Implement getHeader() method.
		if (!$this->hasHeader($name)) {
			return null;
		}
		return $this->headers[$name];
	}


	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getHeaderLine($name): ?string
	{
		// TODO: Implement getHeaderLine() method.
		// TODO: Implement getHeader() method.
		if (!$this->hasHeader($name)) {
			return null;
		}
		return $this->headers[$name];
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return Message
	 */
	public function withHeader($name, $value): static
	{
		// TODO: Implement withHeader() method.
		$newInstance = clone $this;
		$newInstance->headers[$name] = [$value];
		return $newInstance;
	}


	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return $this
	 */
	public function withAddedHeader($name, $value): static
	{
		// TODO: Implement withAddedHeader() method.
		// TODO: Implement withHeader() method.
		$newInstance = clone $this;
		if (!isset($newInstance->headers)) {
			$newInstance->headers[$name] = [$value];
		} else {
			$newInstance->headers[$name][] = $value;
		}
		return $newInstance;
	}


	/**
	 * @param string $name
	 * @return $this
	 */
	public function withoutHeader($name): static
	{
		// TODO: Implement withoutHeader() method.
		$newInstance = clone $this;
		if (!isset($newInstance->headers)) {
			return $newInstance;
		}
		unset($newInstance->headers[$name]);
		return $newInstance;
	}


	/**
	 * @return mixed
	 */
	public function getBody(): mixed
	{
		// TODO: Implement getBody() method.
		return $this->body;
	}


	/**
	 * @param StreamInterface $body
	 * @return $this
	 */
	public function withBody(StreamInterface $body): static
	{
		// TODO: Implement withBody() method.
		$newInstance = clone $this;
		$newInstance->body = $body;
		return $newInstance;
	}

}
