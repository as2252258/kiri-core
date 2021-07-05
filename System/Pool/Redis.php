<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Exception;
use HttpServer\Http\Context;
use Redis as SRedis;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\RedisConnectException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Runtime;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Component
{

	private ?ClientsPool $clientsPool = null;


	/**
	 * @param mixed $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function get(mixed $config, bool $isMaster = false): mixed
	{
		$coroutineName = $this->getPool()->name('Redis:' . $config['host'], $isMaster);
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		}
		if (Coroutine::getCid() === -1) {
			return Context::setContext($coroutineName, $this->createClient($coroutineName, $config));
		}
		$clients = $this->getPool()->getFromChannel($coroutineName);
		if (empty($clients)) {
			return Context::setContext($coroutineName, $this->createClient($coroutineName, $config));
		}
		return Context::setContext($coroutineName, $clients);
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
		if (Coroutine::getCid() === -1) {
			Runtime::enableCoroutine(false);
		}
		$redis = new SRedis();
		if (!$redis->pconnect($config['host'], (int)$config['port'], $config['timeout'])) {
			throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
		}
		if (!empty($config['auth']) && !$redis->auth($config['auth'])) {
			throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
		}
		if (!isset($config['read_timeout'])) {
			$config['read_timeout'] = 10;
		}
		$redis->select($config['databases']);
		$redis->setOption(SRedis::OPT_READ_TIMEOUT, $config['read_timeout']);
		$redis->setOption(SRedis::OPT_PREFIX, $config['prefix']);

		$this->getPool()->increment($name);

		return $redis;
	}


	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function release(array $config, bool $isMaster = false)
	{
		$coroutineName = $this->getPool()->name('Redis:' . $config['host'], $isMaster);
		if (!Context::hasContext($coroutineName)) {
			return;
		}

		$this->getPool()->push($coroutineName, Context::getContext($coroutineName));
		Context::remove($coroutineName);
	}

	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function destroy(array $config, bool $isMaster = false)
	{
		$coroutineName = $this->getPool()->name('Redis:' . $config['host'], $isMaster);
		if (Context::hasContext($coroutineName)) {
			$this->getPool()->decrement($coroutineName);
		}
		Context::remove($coroutineName);
		$this->getPool()->flush(0);
	}


	/**
	 * @return ClientsPool
	 * @throws Exception
	 */
	public function getPool(): ClientsPool
	{
		if (!$this->clientsPool) {
			$this->clientsPool = Snowflake::app()->getClientsPool();
		}
		return $this->clientsPool;
	}


	/**
	 * @param $name
	 * @param $isMaster
	 * @param $max
	 * @throws Exception
	 */
	public function initConnections($name, $isMaster, $max)
	{
		$this->getPool()->initConnections($name, $isMaster, $max);
	}


}
