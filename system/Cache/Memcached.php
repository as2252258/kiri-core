<?php

namespace Snowflake\Cache;


use Exception;
use Snowflake\Abstracts\Component;

/**
 * Class Memcached
 * @package Yoc\cache
 */
class Memcached extends Component implements ICache
{

	/** @var \Memcached */
	private $_memcached;

	public $host = '127.0.0.1';

	public $port = 11211;

	public $timeout = 60;

	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->_memcached = new \Memcached();
		$isConnected = $this->_memcached->addServer(
			env('cache.memcached.host', $this->host),
			env('cache.memcached.port', $this->port),
			env('cache.memcached.timeout', $this->timeout)
		);
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
