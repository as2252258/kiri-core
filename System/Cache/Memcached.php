<?php
declare(strict_types=1);
namespace Snowflake\Cache;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class Memcached
 * @package Yoc\cache
 */
class Memcached extends Component implements ICache
{

	/** @var \Memcached */
	private \Memcached $_memcached;

	public string $host = '127.0.0.1';

	public int $port = 11211;

	public int $timeout = 60;


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$event = Snowflake::app()->event;
		$event->on(Event::RELEASE_ALL, [$this, 'destroy']);
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'release']);

		$id = Config::get('id', false, 'system');
		$this->_memcached = new \Memcached($id);
		$this->addServer();
	}

	/**
	 * @return \Memcached
	 * @throws Exception
	 */
	public function getConnect()
	{
		return $this->_memcached;
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	private function addServer()
	{
		$array = [];
		$memcached = Config::get('cache.memcached');
		if (isset($memcached[0])) {
			foreach ($memcached as $value) {
				$array[] = [$value['host'], $value['port'], $value['weight']];
			}
			$isConnected = $this->_memcached->addServers($array);
		} else {
			$array[] = Config::get('cache.memcached.host', true);
			$array[] = Config::get('cache.memcached.port', true);
			$array[] = Config::get('cache.memcached.weight', true);
			$isConnected = $this->_memcached->addServer(...$array);
		}
		if (!$isConnected) {
			throw new Exception('Cache Memcache Host 127.0.0.1 Connect Fail.');
		}
	}


	/**
	 * @param $key
	 * @param $val
	 * @return mixed|void
	 */
	public function set($key, $val)
	{
		// TODO: Implement set() method.
		if (is_array($val) || is_object($val)) {
			$val = serialize($val);
		}

		$this->_memcached->set($key, $val);
	}

	/**
	 * @param $key
	 * @return mixed|void
	 */
	public function get($key)
	{
		// TODO: Implement get() method.
	}

	/**
	 * @param $key
	 * @param array $hashKeys
	 * @return mixed|void
	 */
	public function hMget($key, array $hashKeys)
	{
		// TODO: Implement hMget() method.
	}

	/**
	 * @param $key
	 * @param array $val
	 * @return mixed|void
	 */
	public function hMset($key, array $val)
	{
		// TODO: Implement hMset() method.
	}

	/**
	 * @param $key
	 * @param $hashKey
	 * @return mixed|void
	 */
	public function hget($key, $hashKey)
	{
		// TODO: Implement hget() method.
	}

	/**
	 * @param $key
	 * @param $hashKey
	 * @param $hashValue
	 * @return mixed|void
	 */
	public function hset($key, $hashKey, $hashValue)
	{
		// TODO: Implement hset() method.
	}

	/**
	 * @param $key
	 * @return bool|void
	 */
	public function exists($key)
	{
		// TODO: Implement exists() method.
	}


}
