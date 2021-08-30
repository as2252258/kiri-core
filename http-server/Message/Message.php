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


	public array $servers = [];


	public array $cookies = [];


	/**
	 * @return array
	 */
	public function getServers(): array
	{
		return $this->servers;
	}


	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		return $this->version;
	}


	/**
	 * @param $name
	 * @param null $value
	 * @param null $expires
	 * @param null $path
	 * @param null $domain
	 * @param null $secure
	 * @param null $httponly
	 * @param null $samesite
	 * @param null $priority
	 * @return static
	 */
	public function withCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null): static
	{
        $this->cookies[$name] = [$value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority];
		return $this;
	}


	/**
	 * @return array
	 */
	public function getCookie(): array
	{
		return $this->cookies;
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
	#[Pure] public function getAccessControlRequestHeaders(): ?string
	{
		return $this->getHeaderLine('Access-Control-Request-Headers');
	}


    /**
     * @return string|null
     */
	#[Pure] public function getAccessControlRequestMethod(): ?string
	{
		return $this->getHeaderLine('Access-Control-Request-Method');
	}


	/**
	 * @param $version
	 * @return $this
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
	 * @return array
	 */
	public function getCookies(): array
	{
		return $this->cookies;
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
	 * @return $this
	 */
	private function parseRequestHeaders(\Swoole\Http\Request $request): static
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
	 * @return $this
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


    /**
     * @param $host
     * @return \Server\Message\Request|\Server\Message\Response
     */
	public function redirectTo($host)
	{
		return $this->withHeader('Location', $host)
			->withStatus(302);
	}
}
