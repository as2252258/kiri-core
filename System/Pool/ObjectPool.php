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
		$this->max = 5000;

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
	 */
	public function createClient(string $name, mixed $config): mixed
	{
		if (isset($config[1])) {
			return new $config[0](...$config[1]);
		}
		return new $config[0]();
	}


	/**
	 * @param string $name
	 * @param $object
	 */
	public function release(string $name, mixed $object)
	{
		if (!isset($this->_waitRecover[$name])) {
			$this->_waitRecover[$name] = [];
		}
		$this->_waitRecover[$name][] = $object;
	}


	/**
	 * @throws ComponentException
	 * 清理等待回收的对象
	 */
	public function destruct()
	{
		if (empty($this->_waitRecover)) {
			return;
		}
		$this->warning('destruct object...');
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
