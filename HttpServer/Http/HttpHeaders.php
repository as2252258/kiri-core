<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */
declare(strict_types=1);

namespace HttpServer\Http;

/**
 * Class HttpHeaders
 * @package Snowflake\Snowflake\Http
 */
class HttpHeaders
{


	private array $_headers = [];


	/**
	 * @param string $uri
	 */
	public function setRequestUri(string $uri)
	{
		$this->_headers['request_uri'] = $uri;
	}


	/**
	 * @param string $method
	 */
	public function setRequestMethod(string $method)
	{
		$this->_headers['request_method'] = $method;
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public function replace($name, $value)
	{
		$this->_headers[$name] = $value;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function addHeader($name, $value)
	{
		$this->_headers[$name] = $value;
	}

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function addHeaders(array $headers): static
	{
		foreach ($headers as $key => $header) {
			$this->_headers[$key] = $header;
		}
		return $this;
	}


	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers)
	{
		$this->_headers = $headers;
	}


	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->_headers;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name): ?string
	{
		return $this->_headers[$name] ?? null;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function get($name, $default = null): mixed
	{
		return $this->_headers[$name] ?? $default;
	}


	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->getHeader('content-type');
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function exists($name): bool
	{
		return isset($this->_headers[$name]) && $this->_headers[$name] != null;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->_headers;
	}


}
