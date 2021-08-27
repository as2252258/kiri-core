<?php

namespace Server\Message;

use JetBrains\PhpStorm\Pure;
use Kiri\Core\Xml;
use Psr\Http\Message\StreamInterface;


/**
 *
 */
trait Message
{

	public string $version;


	public StreamInterface $stream;


	public array $headers = [];


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->version;
	}


	/**
	 * @param $version
	 * @return $this
	 */
	public function withProtocolVersion($version): static
	{
		$class = clone $this;
		$class->version = $version;
		return $class;
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
	 * @param $name
	 * @param $value
	 * @return static
	 */
	public function withHeader($name, $value): static
	{
		$class = clone $this;
		if (!is_array($value)) {
			$value = [$value];
		}
		$class->headers[$name] = $value;
		return $class;
	}


	/**
	 * @param $name
	 * @param $value
	 * @return static
	 * @throws
	 */
	public function withAddedHeader($name, $value): static
	{
		$class = clone $this;
		if (!array_key_exists($name, $class->headers)) {
			throw new \Exception('Headers `' . $name . '` not exists.');
		}
		$class->headers[$name][] = $value;
		return $class;
	}


	/**
	 * @param $name
	 * @return $this
	 */
	public function withoutHeader($name): static
	{
		$class = clone $this;
		unset($class->headers[$name]);
		return $class;
	}


	/**
	 * @return string
	 */
	public function getBody(): string
	{
		return $this->stream;
	}


	/**
	 * @param StreamInterface $body
	 * @return static
	 */
	public function withBody(StreamInterface $body): static
	{
		$class = clone $this;
		$class->stream = $body;
		return $class;
	}


	/**
	 * @param StreamInterface $stream
	 * @return mixed
	 */
	public function parseBody(StreamInterface $stream): mixed
	{
		$content = $stream->getContents();
		if (empty($content)) {
			return $content;
		}
		$contentType = $this->getHeaderLine('content-type');
		if (str_contains($contentType, 'json')) {
			return json_encode($contentType);
		}
		if (str_contains($contentType, 'xml')) {
			return Xml::toArray($contentType);
		}
		if (str_contains($contentType, 'x-www-form-urlencoded')) {
			parse_str($content, $array);
			return $array;
		}
		if (str_contains($contentType, 'serialize')) {
			return unserialize($content);
		}
		return $content;
	}

}
