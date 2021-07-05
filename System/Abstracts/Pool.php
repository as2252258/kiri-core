<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Exception\ConfigException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

/**
 * Class Pool
 * @package Snowflake\Snowflake\Pool
 */
abstract class Pool extends Component
{

	/** @var Channel[] */
	private static array $_items = [];

	public int $max = 60;

	public int $creates = -1;

	public int $lastTime = 0;


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
	public function Heartbeat_detection($ticker, string $name)
	{
		if (env('state') == 'exit') {
			Timer::clear($this->creates);
			$this->creates = -1;
		} else {
			$min = Config::get('databases.pool.min', 1);
			if (($length = $this->getChannel($name)->length()) > $min) {
				$this->flush($min);
			}
		}
	}


	/**
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function flush($retain_number)
	{
		$channels = $this->getChannels();
		foreach ($channels as $name => $channel) {
			$names[] = $name;
			$this->pop($channel, $name, $retain_number);
		}
		static::$_items = [];
		if ($retain_number == 0) {
			Timer::clear($this->creates);
			$this->creates = -1;
		}
	}


	/**
	 * @param $name
	 */
	protected function clearCreateLog($name): void
	{
		if (!isset(static::$hasCreate[$name])) {
			return;
		}
		static::$hasCreate[$name] = 0;
	}


	/**
	 * @param Channel $channel
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop(Channel $channel, $name, $retain_number): void
	{
		if (Coroutine::getCid() === -1) {
			return;
		}
		while ($channel->length() > $retain_number) {
			$connection = $channel->pop();
			if ($connection) {
				unset($connection);
			}
			$this->decrement($name);
		}
	}


	/**
	 * @param $driver
	 * @param $name
	 * @param false $isMaster
	 * @param int $max
	 */
	public function initConnections($driver, $name, bool $isMaster = false, int $max = 60)
	{
		$name = $this->name($driver, $name, $isMaster);
		if (isset(static::$_items[$name]) && static::$_items[$name] instanceof Channel) {
			return;
		}
		if (Coroutine::getCid() === -1) {
			return;
		}
		if ($this->creates === -1) {
			$this->creates = Timer::tick(1000, [$this, 'Heartbeat_detection'], $name);
		}
		static::$_items[$name] = new Channel($max);
		$this->max = $max;
	}


	/**
	 * @param $name
	 * @return Channel
	 * @throws ConfigException
	 */
	private function getChannel($name): Channel
	{
		if (!isset(static::$_items[$name])) {
			static::$_items[$name] = new Channel(Config::get('databases.pool.max', 10));
			if ($this->creates === -1) {
				$this->creates = Timer::tick(1000, [$this, 'Heartbeat_detection'], $name);
			}
		}
		return static::$_items[$name];
	}


	/**
	 * @param $name
	 * @param array $callback
	 * @return array
	 * @throws Exception
	 */
	protected function getFromChannel($name, mixed $callback): mixed
	{
		if (Coroutine::getCid() === -1) {
			return $this->createClient($name, $callback);
		}

		$channel = $this->getChannel($name);
		if (!$channel->isEmpty()) {
			$connection = $channel->pop();
			if ($this->checkCanUse($name, $connection)) {
				return $connection;
			}
		}
		return $this->createClient($name, $callback);
	}


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return mixed
	 */
	abstract public function createClient(string $name, mixed $config): mixed;


	/**
	 * @param $driver
	 * @param $cds
	 * @param false $isMaster
	 * @return string
	 */
	#[Pure] public function name($driver, $cds, bool $isMaster = false): string
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
		if (isset(static::$_items[$name])) {
			return !static::$_items[$name]->isEmpty();
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
		if (!isset(static::$_items[$name])) {
			return 0;
		}
		return static::$_items[$name]->length();
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
		if (Coroutine::getCid() === -1 || !isset(static::$_items[$name])) {
			return;
		}
		$channel = static::$_items[$name];
		$this->pop($channel, $name, 0);
		if ($this->creates > -1) {
			Timer::clear($this->creates);
		}
		$channel->close();
		static::$_items[$name] = null;
	}


	/**
	 * @return Channel[]
	 */
	protected function getChannels(): array
	{
		return static::$_items;
	}


}
