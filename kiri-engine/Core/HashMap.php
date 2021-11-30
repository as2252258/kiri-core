<?php

namespace Kiri\Core;

use JetBrains\PhpStorm\Pure;

class HashMap implements \ArrayAccess
{

	/**
	 * @var array
	 */
	private array $lists = [];


	/**
	 * @param string $key
	 * @param $value
	 */
	public function put(string $key, $value)
	{
		$this->lists[$key] = $value;
	}


	/**
	 * @param string $key
	 * @return mixed
	 */
	#[Pure] public function get(string $key): mixed
	{
		if (!$this->has($key)) {
			return null;
		}
		return $this->lists[$key];
	}


	/**
	 * @param string $key
	 */
	public function del(string $key)
	{
		if (!$this->has($key)) {
			return;
		}
		unset($this->lists[$key]);
	}


	/**
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key): bool
	{
		return array_key_exists($key, $this->lists);
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->lists[$offset]);
	}


	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	#[Pure] public function offsetGet($offset): mixed
	{
		return $this->get($offset);
	}


	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->put($offset, $value);
	}


	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->lists[$offset]);
	}
}
