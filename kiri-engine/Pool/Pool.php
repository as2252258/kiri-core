<?php


namespace Kiri\Pool;


use Database\Mysql\PDO;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\CoordinatorManager;
use Kiri\Context;
use Kiri\Exception\ConfigException;
use Swoole\Coroutine\Channel;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool extends Component
{

	/** @var array<PoolQueue> */
	private static array $_connections = [];

	public int $max = 60;

	use Alias;


	/**
	 * @param $channel
	 * @param $retain_number
	 * @throws Exception
	 */
	public function flush($channel, $retain_number)
	{
		$this->pop($channel, $retain_number);
	}


	/**
	 * @param PoolQueue $channel
	 * @param $retain_number
	 */
	protected function pop(PoolQueue $channel, $retain_number): void
	{
		while ($channel->length() > $retain_number) {
			if (Context::inCoroutine()) {
				$connection = $channel->pop();
				if ($connection instanceof StopHeartbeatCheck) {
					$connection->stopHeartbeatCheck();
				}
			}
		}
	}


	/**
	 * @param $name
	 * @return void
	 * @throws ConfigException
	 */
	public function check($name): void
	{
		CoordinatorManager::utility($name)->waite();

		$channel = $this->channel($name);
		while (($pdo = $channel->pop()) instanceof PDO) {
			$pdo->check();
		}

		CoordinatorManager::utility($name)->done();
	}


	/**
	 * @param $name
	 * @param int $max
	 * @throws ConfigException
	 */
	public function initConnections($name, int $max = 60)
	{
		if (isset(static::$_connections[$name])) {
			$value = static::$_connections[$name];
			if ($value instanceof PoolQueue) {
				return;
			}
		}
		$this->newChannel($name, $max);
		$this->max = $max;
	}


	/**
	 * @param $name
	 * @return PoolQueue
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function channel($name): PoolQueue
	{
		if (!isset(static::$_connections[$name])) {
			$this->newChannel($name);
		}
		if (static::$_connections[$name]->errCode == SWOOLE_CHANNEL_CLOSED) {
			throw new Exception('Channel is Close.');
		}
		return static::$_connections[$name];
	}


	/**
	 * @throws ConfigException
	 */
	private function newChannel($name, $max = null)
	{
		if ($max == null) {
			$max = Config::get('databases.pool.max', 10);
		}
		static::$_connections[$name] = new PoolQueue($max);
	}


	/**
	 * @param $name
	 * @param $callback
	 * @param $minx
	 * @return array
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get($name, $callback, $minx): mixed
	{
		$channel = $this->channel($name);
		if (!$channel->isEmpty()) {
			return $this->maxIdleQuantity($channel, $minx);
		}
		return $callback();
	}


	/**
	 * @param $channel
	 * @param $minx
	 * @return mixed
	 * @throws Exception
	 */
	protected function maxIdleQuantity($channel, $minx): mixed
	{
		$connection = $channel->pop();
		if ($channel->length() > $minx) {
			$this->pop($channel, $minx);
		}
		return $connection;
	}


	/**
	 * @param $name
	 * @return bool
	 * @throws ConfigException
	 */
	public function isNull($name): bool
	{
		return $this->channel($name)->isEmpty();
	}


	/**
	 * @param string $name
	 * @param mixed $client
	 * @return bool
	 * 检查连接可靠性
	 */
	public function checkCanUse(string $name, mixed $client): bool
	{
		return true;
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasItem(string $name): bool
	{
		if (isset(static::$_connections[$name])) {
			return !static::$_connections[$name]->isEmpty();
		}
		return false;
	}


	/**
	 * @param string $name
	 * @return mixed
	 */
	public function size(string $name): mixed
	{
		if (!isset(static::$_connections[$name])) {
			return 0;
		}
		return static::$_connections[$name]->length();
	}


	/**
	 * @param string $name
	 * @param mixed $client
	 * @throws ConfigException
	 */
	public function push(string $name, mixed $client)
	{
		$channel = $this->channel($name);
		if (!$channel->isFull()) {
			$channel->push($client);
		}
		unset($client);
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clean(string $name)
	{
		if (!isset(static::$_connections[$name])) {
			return;
		}
		while (static::$_connections[$name]->length() > 0) {
			$client = static::$_connections[$name]->pop();
			if ($client instanceof StopHeartbeatCheck) {
				$client->stopHeartbeatCheck();
			}
		}
		static::$_connections[$name] = null;
		unset(static::$_connections[$name]);
	}


	/**
	 * @return PoolQueue[]
	 */
	protected function channels(): array
	{
		return static::$_connections;
	}


}
