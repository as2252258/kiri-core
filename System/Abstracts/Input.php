<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;

class Input
{

	private array $_argv = [];


	private string $_command = '';


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
	public function getCommandName(): string
	{
		return $this->_command;
	}


	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */
	public function get($key, $default = null): mixed
	{
		return $this->_argv[$key] ?? $default;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	public function set($key, $value): static
	{
		$this->_argv[$key] = $value;
		return $this;
	}


	/**
	 * @return false|string
	 */
	public function toJson(): bool|string
	{
		return json_encode($this->_argv, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $parameters
	 * @return array
	 * @throws Exception
	 */
	public function resolve($parameters): array
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
	public function getCommand(): string
	{
		return $this->_command;
	}

}
