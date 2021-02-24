<?php


namespace Snowflake\Pool;


use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;

/**
 * Class ObjectPool
 * @package Snowflake\Pool
 */
class ObjectPool extends \Snowflake\Abstracts\Pool
{

	public int $max = 5000;


	/**
	 * @param array $config
	 * @param callable $construct
	 * @return mixed
	 * @throws Exception
	 */
	public function load(mixed $config, callable $construct): mixed
	{
		if (is_object($config)) {
			return $config;
		}
		$object = $this->getFromChannel($name = md5($config), [$config, $construct]);
		if (method_exists($object, 'clean')) {
			$object->clean();
		}
		return $object;
	}


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return mixed
	 */
	public function createClient(string $name, mixed $config): mixed
	{
		return call_user_func($config[1]);
	}


	/**
	 * @param string $name
	 * @param $object
	 */
	public function release(string $name, mixed $object)
	{
		$this->push($name, $object);
	}

}
