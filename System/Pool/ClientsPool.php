<?php


namespace Snowflake\Pool;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;


/**
 * Class ClientsPool
 * @package Snowflake\Pool
 */
class ClientsPool extends Component
{

	/** @var Channel[] */
	private static array $_connections = [];

	public int $max = 60;

	public int $creates = -1;

	protected static array $hasCreate = [];


	/**
	 * @param string $name
	 */
	public function increment(string $name)
	{
		if (!isset(static::$hasCreate[$name])) {
			static::$hasCreate[$name] = 0;
		}
		static::$hasCreate[$name] += 1;
	}


	/**
	 * @param string $name
	 */
	public function decrement(string $name)
	{
		if (!isset(static::$hasCreate[$name])) {
			return;
		}
		if (static::$hasCreate[$name] <= 0) {
			return;
		}
		static::$hasCreate[$name] -= 1;
	}


	/**
	 * @return array
	 * @throws ConfigException
	 */
	private function getClearTime(): array
	{
		$firstClear = Config::get('pool.clear.start', 600);
		$lastClear = Config::get('pool.clear.end', 300);
		return [$firstClear, $lastClear];
	}


	/**
	 * @throws Exception
	 */
	public function Heartbeat_detection($ticker)
	{
		if (env('state') == 'exit') {
			Timer::clear($this->creates);
			$this->creates = -1;
		} else {
			$min = Config::get('databases.pool.min', 1);

			$num = [];
			$total = 0;
			foreach (static::$_connections as $key => $channel) {
				if (!isset($num[$key])) {
					$num[$key] = 0;
				}
				if ($channel->length() > $min) {
					$this->flush($channel, $min);
				}
				$num[$key] += $channel->length();
				if (str_starts_with($key, 'Mysql') && (Snowflake::isWorker() || Snowflake::isTask())
					&& $channel->length() > 0) {
					$this->debug('Worker #' . env('worker') . ' use client -> ' . $key . ':' . $total);
				}
				$total += $channel->length();
			}
			if ($total < 1) {
				Timer::clear($this->creates);
				$this->creates = -1;
			}
		}
	}


	/**
	 * @param $channel
	 * @param $retain_number
	 * @throws Exception
	 */
	public function flush($channel, $retain_number)
	{
		$this->pop($channel, $retain_number);
		static::$_connections = [];
		if ($retain_number == 0) {
			Timer::clear($this->creates);
			$this->creates = -1;
		}
	}


	/**
	 * @param Channel $channel
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop(Channel $channel, $retain_number): void
	{
		if (Coroutine::getCid() === -1) {
			return;
		}
		while ($channel->length() > $retain_number) {
			$connection = $channel->pop();
			if ($connection) {
				unset($connection);
			}
		}
	}


	/**
	 * @param $name
	 * @param false $isMaster
	 * @param int $max
	 */
	public function initConnections($name, bool $isMaster = false, int $max = 60)
	{
		$name = $this->name($name, $isMaster);
		if (isset(static::$_connections[$name]) && static::$_connections[$name] instanceof Channel) {
			return;
		}
		if (Coroutine::getCid() === -1) {
			return;
		}
		if ($this->creates === -1) {
			$this->creates = Timer::tick(3000, [$this, 'Heartbeat_detection']);
		}
		static::$_connections[$name] = new Channel($max);
		$this->max = $max;
	}


	/**
	 * @param $name
	 * @return Channel
	 * @throws ConfigException
	 */
	private function getChannel($name): Channel
	{
		if (!isset(static::$_connections[$name])) {
			static::$_connections[$name] = new Channel(Config::get('databases.pool.max', 10));
			if ($this->creates === -1) {
				$this->creates = Timer::tick(3000, [$this, 'Heartbeat_detection']);
			}
		}
		return static::$_connections[$name];
	}


	/**
	 * @param $name
	 * @return array
	 * @throws Exception
	 */
	public function getFromChannel($name): mixed
	{
		$channel = $this->getChannel($name);
		if (!$channel->isEmpty()) {
			$connection = $channel->pop();
			if ($this->checkCanUse($name, $connection)) {
				return $connection;
			}
		}
		return null;
	}


	/**
	 * @param $cds
	 * @param false $isMaster
	 * @return string
	 */
	#[Pure] public function name($cds, bool $isMaster = false): string
	{
		if ($isMaster === true) {
			return $cds . '_master';
		} else {
			return $cds . '_slave';
		}
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
	 * @param array $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function get(mixed $config, bool $isMaster): mixed
	{
		throw new Exception('Undefined system processing function.');
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
		if (Coroutine::getCid() === -1) {
			return 0;
		}
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
		if (Coroutine::getCid() === -1) {
			return;
		}
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
		if (Coroutine::getCid() === -1 || !isset(static::$_connections[$name])) {
			return;
		}
		$channel = static::$_connections[$name];
		$this->pop($channel, 0);
	}


	/**
	 * @return Channel[]
	 */
	protected function getChannels(): array
	{
		return static::$_connections;
	}


}
