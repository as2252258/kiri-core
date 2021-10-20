<?php


namespace Kiri\Pool;


use Exception;
use Http\Handler\Context;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Pool\Helper\SplQueue;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool extends Component
{

	/** @var Channel[] */
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
	 * @param Channel $channel
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop(Channel $channel, $retain_number): void
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
	 * @param false $isMaster
	 * @param int $max
	 * @throws ConfigException
	 */
	public function initConnections($name, bool $isMaster = false, int $max = 60)
	{
		$name = $this->name($name, $isMaster);
		if (isset(static::$_connections[$name])) {
			$value = static::$_connections[$name];
			if ($value instanceof Channel || $value instanceof SplQueue) {
				return;
			}
		}
		$this->newChannel($name, $max);
		$this->max = $max;
	}


	/**
	 * @param $name
	 * @return Channel|SplQueue
	 * @throws ConfigException
	 * @throws Exception
	 */
	private function getChannel($name): Channel|SplQueue
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
		if (Coroutine::getCid() === -1) {
			static::$_connections[$name] = new SplQueue($max);
		} else {
			static::$_connections[$name] = new Channel($max);
		}
	}


	/**
	 * @param $name
	 * @param $callback
	 * @return array
	 * @throws ConfigException
	 */
	public function get($name, $callback): mixed
	{
		$channel = $this->getChannel($name);
		if (!$channel->isEmpty()) {
			return $this->maxIdleQuantity($channel);
		}
		return $callback();
	}


	/**
	 * @param $channel
	 * @return mixed
	 * @throws ConfigException
	 * @throws Exception
	 */
	private function maxIdleQuantity($channel): mixed
	{
		$connection = $channel->pop();
		$minx = Config::get('databases.pool.min', 1);
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
		return $this->getChannel($name)->isEmpty();
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
		$channel = $this->getChannel($name);
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

		var_dump(static::$_connections[$name]);

		/** @var Channel|SplQueue $connection */
		foreach (static::$_connections[$name] as $connection) {
			$client = $connection->pop();
			if ($client instanceof StopHeartbeatCheck) {
				$client->stopHeartbeatCheck();
			}
		}
		static::$_connections[$name] = null;
		unset(static::$_connections[$name]);
	}


	/**
	 * @return Channel[]
	 */
	protected function getChannels(): array
	{
		return static::$_connections;
	}


}
