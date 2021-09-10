<?php

namespace Protocol\Message;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\StreamInterface;


/**
 *
 */
trait Message
{

	/**
	 * @var string
	 */
	protected string $version;


	/**
	 * @var StreamInterface
	 */
	protected StreamInterface $stream;


	/**
	 * @var array
	 */
	protected array $headers = [];


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->version;
	}


	/**
	 * @param $version
	 * @return static
	 */
	public function withProtocolVersion($version): static
	{
		$this->version = $version;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return array_key_exists($name, $this->headers);
	}


	/**
	 * @param $name
	 * @return string|array|null
	 */
	#[Pure] public function getHeader($name): string|null|array
	{
		if (!$this->hasHeader($name)) {
			return null;
		}
		return $this->headers[$name];
	}


	/**
	 * @param \Swoole\Http\Request $request
	 * @return static
	 */
	public function parseRequestHeaders(\Swoole\Http\Request $request): static
	{
		$index = strpos($request->getData(), "\r\n\r\n");
		$headers = explode("\r\n", substr($request->getData(), 0, $index));
		array_shift($headers);
		foreach ($headers as $header) {
			[$key, $value] = explode(': ', $header);
			$this->addRequestHeader($key, $value);
		}
		return $this;
	}


	/**
	 * @param $key
	 * @param $value
	 */
	private function addRequestHeader($key, $value)
	{
		$this->headers[$key] = [$value];
	}


	/**
	 * @param $name
	 * @return string|null
	 */
	#[Pure] public function getHeaderLine($name): string|null
	{
		if ($this->hasHeader($name)) {
			return implode(';', $this->headers[$name]);
		}
		return null;
	}


	/**
	 * @return string|null
	 */
	#[Pure] public function getContentType(): ?string
	{
		return $this->getHeaderLine('Content-Type');
	}


	/**
	 * @param $name
	 * @param $value
	 * @return static
	 */
	public function withHeader($name, $value): static
	{
		if (!is_array($value)) {
			$value = [$value];
		}
		$this->headers[$name] = $value;
		return $this;
	}


	/**
	 * @param $name
	 * @param $value
	 * @return static
	 * @throws
	 */
	public function withAddedHeader($name, $value): static
	{
		if (!array_key_exists($name, $this->headers)) {
			throw new \Exception('Headers `' . $name . '` not exists.');
		}
		$this->headers[$name][] = $value;
		return $this;
	}


	/**
	 * @param $name
	 * @return static
	 */
	public function withoutHeader($name): static
	{
		unset($this->headers[$name]);
		return $this;
	}


	/**
	 * @return string
	 */
	public function getBody(): string
	{
		return $this->stream->getContents();
	}


	/**
	 * @param StreamInterface $body
	 * @return static
	 */
	public function withBody(StreamInterface $body): static
	{
		$this->stream = $body;
		return $this;
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

}
