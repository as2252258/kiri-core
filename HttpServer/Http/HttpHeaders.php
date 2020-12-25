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
	 * @var string[]
	 */
	private array $headers = [];

	/**
	 * @var string[]
	 */
	private array $response = [];

	/**
	 * HttpHeaders constructor.
	 * @param $headers
	 */
	public function __construct($headers)
	{
		$this->headers = $headers;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setHeader($name, $value)
	{
		$this->response[$name] = $value;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers)
	{
		foreach ($headers as $key => $val) {
			$this->response[$key] = $val;
		}
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function replace($name, $value)
	{
		$this->headers[$name] = $value;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
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
		if (!empty($this->headers)) {
			$headers = array_merge($this->headers, $headers);
		}
		$this->headers = $headers;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getResponseHeaders(): array
	{
		return $this->response;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->headers;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name): ?string
	{
		if (!isset($this->headers[$name])) {
			return null;
		}
		return $this->headers[$name];
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
		return isset($this->headers[$name]) && $this->headers[$name] != null;
	}


	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

}
