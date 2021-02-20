<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use HttpServer\Http\Context;
use Redis as SRedis;
use RedisException;
use Snowflake\Exception\RedisConnectException;
use Swoole\Coroutine;
use Exception;
use Swoole\Timer;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Pool
{

	use Timeout;


	private int $_create = 0;

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
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster = false): mixed
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis:' . $name, $isMaster);
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		}
		if (!$this->hasItem($coroutineName)) {
			$this->newClient($config, $coroutineName);
		}
		[$time, $clients] = $this->get($coroutineName);
		if ($clients === null) {
			return $this->getConnection($config, $coroutineName);
		}
		return Context::setContext($coroutineName, $clients);
	}


	/**
	 * @param $config
	 * @param $coroutineName
	 * @return SRedis|null
	 * @throws Exception
	 */
	private function newClient($config, $coroutineName): \Redis|null
	{
		$this->printClients($config['host'], $coroutineName, true);
		$this->createConnect([$config, $coroutineName], $coroutineName, function ($config, $coroutineName) {
			$redis = new SRedis();
			if (!$redis->connect($config['host'], (int)$config['port'], $config['timeout'])) {
				throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
			}
			if (empty($config['auth']) || !$redis->auth($config['auth'])) {
				throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
			}
			if (!isset($config['read_timeout'])) {
				$config['read_timeout'] = 10;
			}
			$redis->select($config['databases']);
			$redis->setOption(SRedis::OPT_READ_TIMEOUT, $config['read_timeout']);
			$redis->setOption(SRedis::OPT_PREFIX, $config['prefix']);

			$this->_create += 1;

			return $redis;
		});
		return $this->get($coroutineName)[1];
	}


	/**
	 * @param $cds
	 * @param $coroutineName
	 * @param false $isBefore
	 */
	public function printClients($cds, $coroutineName, $isBefore = false)
	{
		$this->warning(($isBefore ? 'before ' : '') . 'create client[address: ' . $cds . ', ' . env('workerId') . ', coroutine: ' . Coroutine::getCid() . ', has num: ' . $this->size($coroutineName) . ', has create: ' . $this->_create . ']');
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
		$this->remove($coroutineName);
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function destroy(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}

		$this->desc($coroutineName);

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
			if (!($client instanceof SRedis)) {
				$result = false;
			} else if (!$client->isConnected() || !$client->ping('connect.')) {
				$result = false;
			} else {
				$result = true;
			}
		} catch (\Throwable $exception) {
			$this->error($exception->getMessage());
			$result = false;
		} finally {
			if (!$result) {
				$this->desc($name);
			}
			return $result;
		}
	}

	/**
	 * @param string $name
	 */
	public function desc(string $name)
	{
		$this->_create -= 1;
	}


}
