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

	private array $_waitRecover = [];


	/**
	 * set pool max length
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function init()
	{
		$this->max = 100;

		$event = Snowflake::app()->getEvent();
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'destruct']);
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
		$this->_waitRecover[$name][] = $object;
	}


	public function destruct()
	{
		if (empty($this->_waitRecover)) {
			return;
		}
		foreach ($this->_waitRecover as $name => $value) {
			if (empty($value)) {
				continue;
			}
			foreach ($value as $object) {
				if (method_exists($object, 'clean')) {
					$object->clean();
				}
				$this->push($name, $object);
			}
		}
		$this->_waitRecover = [];
	}

}
