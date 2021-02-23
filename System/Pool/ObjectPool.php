<?php


namespace Snowflake\Pool;


use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class ObjectPool
 * @package Snowflake\Pool
 */
class ObjectPool extends \Snowflake\Abstracts\Pool
{


	/**
	 * set pool max length
	 */
	public function init()
	{
		$this->max = 100;
	}


	/**
	 * @param array $config
	 * @param array $construct
	 * @return mixed
	 * @throws Exception
	 */
	public function load(mixed $config, array $construct = []): mixed
	{
		if (is_object($config)) {
			return $config;
		}
		return $this->getFromChannel($name = md5($config), [$config, $construct]);
	}


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function createClient(string $name, mixed $config): mixed
	{
		return Snowflake::createObject(...$config);
	}


	/**
	 * @param string $name
	 * @param $object
	 */
	public function release(string $name, mixed $object)
	{
		$newObject = clone $object;
		if (method_exists($newObject, 'clean')) {
			$newObject->clean();
		}
		$this->push($name, $newObject);
	}

}
