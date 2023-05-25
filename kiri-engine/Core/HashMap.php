<?php

namespace Kiri\Core;

use Exception;
use JetBrains\PhpStorm\Pure;
use ReturnTypeWillChange;
use Traversable;

class HashMap implements \ArrayAccess, \IteratorAggregate
{

	/**
	 * @var array
	 */
	private array $lists = [];


	/**
	 * @return Traversable
	 */
	public function getIterator(): Traversable
	{
		return new \ArrayIterator($this->lists);
	}


	/**
	 * @return bool
	 */
	public function hasItem(): bool
	{
		return count($this->lists) > 0;
	}


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
	 * @param $value
	 * @return void
	 * @throws Exception
	 */
	public function append(string $key, $value): void
	{
		if (!$this->has($key)) {
			$this->lists[$key] = [];
		} else if (!is_array($this->lists[$key])) {
			throw new Exception('Source must a array.');
		}
		$this->lists[$key][] = $value;
	}


    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
	#[Pure] public function get(string $key,  mixed $default = null): mixed
	{
		if (!$this->has($key)) {
			return $default;
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
	public function offsetExists(mixed $offset): bool
	{
		return isset($this->lists[$offset]);
	}


	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	#[Pure] public function offsetGet(mixed $offset): mixed
	{
		return $this->get($offset);
	}


	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[ReturnTypeWillChange]
	public function offsetSet(mixed $offset, mixed $value)
	{
		$this->put($offset, $value);
	}


	/**
	 * @param mixed $offset
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset(mixed $offset)
	{
		unset($this->lists[$offset]);
	}


	public static function Tree(HashMap $root, string $leaf)
	{
		if ($root->has($leaf)) {
			$hashMap = $root->get($leaf);
		} else {
			$hashMap = new HashMap();
			$root->put($leaf, $hashMap);
		}
		return $hashMap;
	}

}
