<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */

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
	private $headers = [];

	/**
	 * @var string[]
	 */
	private $response = [];

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
	public function addHeaders(array $headers)
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
	public function getResponseHeaders()
	{
		return $this->response;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getHeader($name)
	{
		return $this->headers[$name] ?? null;
	}


	/**
	 * @param $name
	 * @return mixed|string|null
	 */
	public function get($name)
	{
		return $this->getHeader($name);
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function exists($name)
	{
		return isset($this->headers[$name]) && $this->headers[$name] != null;
	}


	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

}
