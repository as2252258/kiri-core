<?php


namespace Snowflake\Abstracts;


use Exception;

class Input
{

	private $_argv = [];


	private $_command = '';


	/**
	 * Input constructor.
	 * @param $argv
	 * @throws
	 */
	public function __construct($argv)
	{
		$this->_argv = $this->resolve($argv);
	}


	/**
	 * @return string
	 */
	public function getCommandName()
	{
		return $this->_command;
	}


	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function get($key, $default = null)
	{
		return $this->_argv[$key] ?? $default;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function set($key, $value)
	{
		$this->_argv[$key] = $value;
		return $this;
	}


	/**
	 * @return false|string
	 */
	public function toJson()
	{
		return json_encode($this->_argv, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $parameters
	 * @return array
	 * @throws Exception
	 */
	public function resolve($parameters)
	{
		$arrays = [];
		$parameters = array_slice($parameters, 1);
		$this->_command = array_shift($parameters);
		foreach ($parameters as $parameter) {
			$explode = explode('=', $parameter);
			if (count($explode) < 2) {
				continue;
			}
			$arrays[array_shift($explode)] = current($explode);
		}
		return $arrays;
	}


	/**
	 * @return string
	 */
	public function getCommand()
	{
		return $this->_command;
	}

}
