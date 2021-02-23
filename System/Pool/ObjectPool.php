<?php


namespace Snowflake\Pool;


use Exception;
use ReflectionException;
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
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function get(mixed $config, bool $isMaster = false): mixed
	{
		if (is_object($config)) {
			return $config;
		}
		return $this->getFromChannel(md5($config), $config);
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
		// TODO: Implement createClient() method.
		return Snowflake::createObject($config);
	}


	/**
	 * @param string $name
	 * @param $object
	 */
	public function release(string $name, mixed $object)
	{
		if (method_exists($object, 'clean')) {
			$object->clean();
		}
		$this->push($name, $object);
	}

}
