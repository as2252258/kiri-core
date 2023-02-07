<?php

namespace Kiri\Redis;

use Exception;
use Kiri;
use Kiri\Abstracts\Logger;
use Kiri\Exception\RedisConnectException;
use Kiri\Pool\StopHeartbeatCheck;
use Kiri\Server\Events\OnWorkerExit;
use RedisException;
use Swoole\Timer;
use function error;


/**
 *
 */
class Helper implements StopHeartbeatCheck
{

	private ?\Redis $pdo = null;

	public string $host;

	public int $port;

	public int $database = 0;

	public string $auth = '';

	public string $prefix = '';

	public int $timeout = 30;

	public int $read_timeout = 30;

	public array $pool = [];

	private int $_timer = -1;


	/**
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->host = $config['host'];
		$this->port = $config['port'];
		$this->database = $config['databases'];
		$this->auth = $config['auth'];
		$this->prefix = $config['prefix'];
		$this->timeout = $config['timeout'];
		$this->read_timeout = $config['read_timeout'];
		$this->pool = $config['pool'];
	}


	/**
	 * clear client heartbeat
	 */
	public function stopHeartbeatCheck(): void
	{
		$this->_timer = -1;
	}


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws RedisConnectException|RedisException
	 */
	public function __call(string $name, array $arguments)
	{
		if (!method_exists($this, $name)) {
			return $this->_pdo()->{$name}(...$arguments);
		}
		return $this->{$name}(...$arguments);
	}


	/**
	 * @return \Redis
	 * @throws Exception
	 * @throws RedisException
	 */
	public function _pdo(): \Redis
	{
		if (!($this->pdo instanceof \Redis) || !$this->pdo->ping('isOk')) {
			$this->pdo = $this->newClient();
		}
		return $this->pdo;
	}


	/**
	 * @return \Redis
	 * @throws Exception
	 */
	private function newClient(): \Redis
	{
		$redis = new \Redis();
		if (!$redis->connect($this->host, $this->port, $this->timeout)) {
			throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $this->host, $this->port));
		}
		if (!empty($this->auth) && !$redis->auth($this->auth)) {
			throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $this->host, $this->auth));
		}
		if ($this->read_timeout < 0) {
			$this->read_timeout = 0;
		}
		$redis->select($this->database);
		if ($this->read_timeout > 0) {
			$redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->read_timeout);
		}
		$redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
		return $redis;

	}

}
