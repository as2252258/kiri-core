<?php
declare(strict_types=1);


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
	 * @return mixed
	 * @throws RedisConnectException
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster = false): mixed
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis:' . $name, $isMaster);
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		} else if (!$this->hasItem($coroutineName)) {
			return $this->saveClient($coroutineName, $this->createConnect($config, $coroutineName));
		}
		return $this->getByChannel($coroutineName, $config);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return mixed
	 * @throws Exception
	 */
	public function getByChannel($coroutineName, $config): mixed
	{
		if (!$this->hasItem($coroutineName)) {
			return $this->saveClient($coroutineName, $this->createConnect($config, $coroutineName));
		}
		$clients = $this->get($coroutineName);
		if ($clients[1] === null) {
			return $this->getByChannel($coroutineName, $config);
		}
		return $this->saveClient($coroutineName, $clients[1]);
	}


	/**
	 * @param $coroutineName
	 * @param $client
	 * @return mixed
	 * @throws Exception
	 */
	private function saveClient($coroutineName, $client): mixed
	{
		return Context::setContext($coroutineName, $client);
	}


	/**
	 * @param array $config
	 * @param string $coroutineName
	 * @return SRedis
	 * @throws RedisConnectException
	 */
	private function createConnect(array $config, string $coroutineName): SRedis
	{
		$redis = new SRedis();
		if (!$redis->connect($config['host'], (int)$config['port'], $config['timeout'])) {
			throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
		}
		if (empty($config['auth']) || !$redis->auth($config['auth'])) {
			throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
		}
		$this->success('create redis client -> ' . $config['host'] . ':' . $this->size($coroutineName));
		if (!isset($config['read_timeout'])) {
			$config['read_timeout'] = 10;
		}
		$redis->select($config['databases']);
		$redis->setOption(SRedis::OPT_READ_TIMEOUT, $config['read_timeout']);
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
		$coroutineName = $this->name('redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}
		$this->push($coroutineName, Context::getContext($coroutineName));
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 */
	public function destroy(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}
		$this->remove($coroutineName);
		$this->clean($coroutineName);
	}

	/**
	 * @param $coroutineName
	 */
	public function remove(string $coroutineName)
	{
		Context::deleteId($coroutineName);
	}

	/**
	 * @param $name
	 * @param $time
	 * @param $client
	 * @return bool
	 * @throws Exception
	 */
	public function checkCanUse(string $name, int $time, mixed $client): bool
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
		} catch (\Throwable $exception) {
			return $result = false;
		} finally {
			if (!$result) {
				$this->desc($name);
			}
		}
	}

	public function desc(string $name)
	{
		// TODO: Implement desc() method.
	}


}
