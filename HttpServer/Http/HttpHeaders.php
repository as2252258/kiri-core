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


	/**
	 * @param string $uri
	 */
	public function setRequestUri(string $uri)
	{
		$this->replace('request_uri', $uri);
	}


	/**
	 * @param string $method
	 */
	public function setRequestMethod(string $method)
	{
		$this->replace('request_method', $method);
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public function replace($name, $value)
	{
		$this->addHeaders([$name => $value]);
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function addHeader($name, $value)
	{
		$this->addHeaders([$name => $value]);
	}

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function addHeaders(array $headers): static
	{
		if (empty($headers)) {
			return $this;
		}
		$request = Context::getContext('request');
		if (!empty($request->headers)) {
			$headers = array_merge($request->headers, $headers);
		}
		$request->headers = $headers;
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->___call();
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name): ?string
	{
		$headers = $this->___call();
		if (!isset($headers[$name])) {
			return null;
		}
		return $headers[$name];
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function get($name, $default = null): mixed
	{
		if (($value = $this->getHeader($name)) === null) {
			return $default;
		}
		return $value;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function exists($name): bool
	{
		$headers = $this->___call();
		return isset($headers[$name]) && $headers[$name] != null;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->___call();
	}


	/**
	 * @return mixed
	 */
	private function ___call(): array
	{
		return Context::getContext('request')->header ?? [];
	}


}
