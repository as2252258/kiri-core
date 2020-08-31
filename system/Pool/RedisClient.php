<?php


namespace Snowflake\Pool;


use HttpServer\Http\Context;
use Redis;
use RedisException;
use Swoole\Coroutine;
use Exception;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class RedisClient extends Pool
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
			$this->success('create redis client -> ' . $config['host'] . ':' . $this->hasLength($coroutineName));
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
		$this->info('redis client has :' . $this->hasLength($coroutineName));
		if (!$this->hasItem($coroutineName)) {
			$this->success('create redis client -> ' . $config['host'] . ':' . $this->hasLength($coroutineName));
			return $this->saveClient($coroutineName, $this->createConnect($config));
		}
		[$time, $client] = $this->get($coroutineName, -1);
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
	 * @return Redis
	 * @throws Exception
	 */
	private function createConnect(array $config)
	{
		$redis = new Redis();
		if (!$redis->connect($config['host'], $config['port'], $config['timeout'])) {
			throw new Exception('The Redis Connect Fail.');
		}
		if (empty($config['auth']) || !$redis->auth($config['auth'])) {
			throw new Exception(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
		}
		if (!isset($config['read_timeout'])) {
			$config['read_timeout'] = 10;
		}
		$redis->select($config['databases']);
		$redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
		$redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
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
	 * @param $time
	 * @param $client
	 * @return bool|mixed
	 * @throws RedisException
	 */
	public function checkCanUse($time, $client)
	{
		if ($time + 60 * 10 < time()) {
			return false;
		}
		if (!($client instanceof Redis)) {
			return false;
		}
		if (!$client->isConnected() || !$client->ping('connect.')) {
			return false;
		}
		return true;
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
