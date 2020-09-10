<?php


namespace Snowflake\Pool;


use HttpServer\Http\Context;
use Redis as SRedis;
use RedisException;
use Snowflake\Exception\RedisConnectException;
use Swoole\Coroutine;
use Exception;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Pool
{

	/**
	 * @param $value
	 */
	public function setLength($value)
	{
		$this->max = $value;
	}


	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @return mixed|null
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		[$coroutineId, $coroutineName] = $this->getIndex('redis:' . $name, $isMaster);
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		} else if (!$this->hasItem($coroutineName)) {
			return $this->saveClient($coroutineName, $this->createConnect($config));
		}
		return $this->getByChannel($coroutineName, $config);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return mixed
	 * @throws Exception
	 */
	public function getByChannel($coroutineName, $config)
	{
		if (!$this->hasItem($coroutineName)) {
			return $this->saveClient($coroutineName, $this->createConnect($config));
		}
		[$time, $client] = $this->get($coroutineName);
		if ($client === null) {
			return $this->getByChannel($coroutineName, $config);
		}
		return $this->saveClient($coroutineName, $client);
	}


	/**
	 * @param $coroutineName
	 * @param $client
	 * @return mixed
	 * @throws Exception
	 */
	private function saveClient($coroutineName, $client)
	{
		return Context::setContext($coroutineName, $client);
	}


	/**
	 * @param array $config
	 * @return SRedis
	 * @throws Exception
	 */
	private function createConnect(array $config)
	{
		$redis = new SRedis();
		if (!$redis->connect($config['host'], $config['port'], $config['timeout'])) {
			throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
		}
		if (empty($config['auth']) || !$redis->auth($config['auth'])) {
			throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
		}
		if (!isset($config['read_timeout'])) {
			$config['read_timeout'] = 10;
		}
		$redis->select($config['databases']);
		$redis->setOption(SRedis::OPT_READ_TIMEOUT, -1);
		$redis->setOption(SRedis::OPT_PREFIX, $config['prefix']);
		return $redis;
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 */
	public function release(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		[$coroutineId, $coroutineName] = $this->getIndex('redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}

		$client = Context::getContext($coroutineName);

		$this->push($coroutineName, $client);
		$this->remove($coroutineName);
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 */
	public function destroy(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		[$coroutineId, $coroutineName] = $this->getIndex('redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}
		$this->remove($coroutineName);
		$this->clean($coroutineName);
	}

	/**
	 * @param $coroutineName
	 */
	public function remove($coroutineName)
	{
		Context::deleteId($coroutineName);
	}

	/**
	 * @param $name
	 * @param $time
	 * @param $client
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function checkCanUse($name, $time, $client)
	{
		try {
			if ($time + 60 * 10 < time()) {
				return $result = false;
			}
			if (!($client instanceof SRedis)) {
				return $result = false;
			}
			if (!$client->isConnected() || !$client->ping('connect.')) {
				return $result = false;
			}
			return $result = true;
		} catch (Exception $exception) {
			return $result = false;
		} finally {
			if (!$result) {
				$this->desc($name);
			}
		}
	}

	public function desc($name)
	{
		// TODO: Implement desc() method.
	}

	/**
	 * @param $name
	 * @param false $isMaster
	 * @return array
	 */
	private function getIndex($name, $isMaster = false)
	{
		return [Coroutine::getCid(), $this->name($name, $isMaster)];
	}


}
