<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Exception;
use HttpServer\Http\Context;
use Redis as SRedis;
use Snowflake\Abstracts\Pool;
use Snowflake\Exception\RedisConnectException;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Pool
{


	public int $_create = 0;

	/**
	 * @param $value
	 */
	public function setLength($value)
	{
		$this->max = $value;
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function canCreate(string $name): bool
	{
		if (!isset($this->hasCreate[$name])) {
			$this->hasCreate[$name] = 0;
		}
		return $this->hasCreate[$name] >= $this->max;
	}


	/**
	 * @param mixed $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function get(mixed $config, $isMaster = false): mixed
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
		if (($redis = Context::getContext($coroutineName)) instanceof \Redis) {
			return $redis;
		}
		return Context::setContext($coroutineName, $this->getFromChannel($coroutineName, $config));
	}


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return SRedis
	 * @throws RedisConnectException
	 * @throws Exception
	 */
	public function createClient(string $name, mixed $config): SRedis
	{
		$redis = new SRedis();
		if (!$redis->pconnect($config['host'], (int)$config['port'], $config['timeout'])) {
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

		$this->increment($name);

		return $redis;
	}


	/**
	 * @param array $config
	 * @param bool $isMaster
	 */
	public function release(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}

		$this->push($coroutineName, Context::getContext($coroutineName));
		$this->lastTime = time();
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function destroy(array $config, $isMaster = false)
	{
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
		$coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
		if (Context::hasContext($coroutineName)) {
			$this->decrement($coroutineName);
		}
		$this->flush(0);
	}

	/**
	 * @param string $name
	 * @param mixed $client
	 * @return bool
	 * @throws Exception
	 */
	public function checkCanUse(string $name, mixed $client): bool
	{
		try {
			if (!($client instanceof SRedis)) {
				$result = false;
			} else {
				$result = true;
			}
		} catch (\Throwable $exception) {
			$this->addError($exception, 'redis');
			$result = false;
		} finally {
			if (!$result) {
				$this->decrement($name);
			}
			return $result;
		}
	}



}
