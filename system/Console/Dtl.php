<?php


namespace Snowflake\Console;


use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;

/**
 * Class Dtl
 * @package Snowflake\Console
 */
class Dtl
{

	private $parameters;

	private $command = '';

	/**
	 * Dtl constructor.
	 * @param $parameters
	 * @throws
	 */
	public function __construct($parameters)
	{
		$this->parameters = $this->resolve($parameters);
	}

	/**
	 * @return string
	 */
	public function getCommandName()
	{
		return $this->command;
	}

	/**
	 * @param $parameters
	 * @return array
	 * @throws \Exception
	 */
	public function resolve($parameters)
	{
		$arrays = [];
		$parameters = array_slice($parameters, 1);

		$this->command = array_shift($parameters);
		foreach ($parameters as $parameter) {
			$explode = explode('=', $parameter);
			if (count($explode) < 2) {
				continue;
			}
			$arrays[array_shift($explode)] = current($explode);
		}

		$request = new Request();
		$request->fd = 0;
		$request->params = new HttpParams([], $arrays, []);
		$request->headers = new HttpHeaders([]);

		Context::setContext('request', $request);
		return $arrays;
	}


	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function get($key, $default = null)
	{
		return $this->parameters[$key] ?? $default;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function set($key, $value)
	{
		$this->parameters[$key] = $value;
		return $this;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function setBatch(array $array)
	{
		if (empty($array)) {
			return $this;
		}
		foreach ($array as $key => $value) {
			$this->parameters[$key] = $value;
		}
		return $this;
	}

	/**
	 * @return array
	 * 获取
	 */
	public function getAll()
	{
		return $this->parameters;
	}

	/**
	 * @return null
	 * 清理
	 */
	public function clear()
	{
		return $this->parameters = null;
	}

	/**
	 * @return false|string
	 */
	public function toJson()
	{
		return json_encode($this->parameters, JSON_UNESCAPED_UNICODE);
	}
}
