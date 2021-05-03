<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;
use HttpServer\Http\Context;
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
	private array $_items = [];

	public int $max = 60;

	public int $creates = -1;

	public int $lastTime = 0;


	protected array $hasCreate = [];


	/**
	 * @param string $name
	 */
	public function increment(string $name)
	{
		if (!isset($this->hasCreate[$name])) {
			$this->hasCreate[$name] = 0;
		}
		$this->hasCreate[$name] += 1;
	}


	/**
	 * @param string $name
	 */
	public function decrement(string $name)
	{
		if (!isset($this->hasCreate[$name])) {
			return;
		}
		if ($this->hasCreate[$name] <= 0) {
			return;
		}
		$this->hasCreate[$name] -= 1;
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
	public function Heartbeat_detection()
	{
		if (env('state') == 'exit') {
			Timer::clear($this->creates);
			$this->creates = -1;
		} else if ($this->lastTime != 0) {
			[$firstClear, $lastClear] = $this->getClearTime();
			if ($this->lastTime + $firstClear < time()) {
				$this->flush(0);
			} else if ($this->lastTime + $lastClear < time()) {
				$this->flush(2);
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
		if (!isset($this->hasCreate[$name])) {
			return;
		}
		$this->hasCreate[$name] = 0;
	}


	/**
	 * @param $channel
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop($channel, $name, $retain_number): void
	{
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
	public function initConnections($driver, $name, $isMaster = false, $max = 60)
	{
		$name = $this->name($driver, $name, $isMaster);
		if (isset($this->_items[$name]) && $this->_items[$name] instanceof Channel) {
			return;
		}
        if (Coroutine::getCid() === -1) {
			return;
		}
		$this->_items[$name] = new Channel((int)$max);
		$this->max = (int)$max;
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
		if (!isset($this->_items[$name])) {
			$this->_items[$name] = new Channel($this->max);
		}
		if ($this->_items[$name]->isEmpty()) {
			$this->createByCallback($name, $callback);
		}
		$connection = $this->_items[$name]->pop();
		if (!$this->checkCanUse($name, $connection)) {
			return $this->createClient($name, $callback);
		} else {
			return $connection;
		}
	}


	/**
	 * @param $name
	 * @param mixed $callback
	 * @throws Exception
	 */
	private function createByCallback($name, mixed $callback): void
	{
		if ($this->creates === -1 && !is_callable($callback)) {
			$this->creates = Timer::tick(1000, [$this, 'Heartbeat_detection']);
		}
		$this->_items[$name]->push($this->createClient($name, $callback));
	}


	abstract public function createClient(string $name, mixed $config): mixed;


	/**
	 * @param $driver
	 * @param $cds
	 * @param false $isMaster
	 * @return string
	 */
	#[Pure] public function name($driver, $cds, $isMaster = false): string
	{
		if ($isMaster === true) {
			return $cds . '_master';
		} else {
			return $cds . '_slave';
		}
	}


	/**
	 * @param string $name
	 * @param $client
	 * @return bool
	 * 检查连接可靠性
	 */
	public function checkCanUse(string $name, mixed $client): bool
	{
		return true;
	}


	/**
	 * @param array $config
	 * @param $isMaster
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
	public function canCreate(string $name): bool
	{
		if (!isset($this->hasCreate[$name])) {
			$this->hasCreate[$name] = 0;
		}
		return $this->hasCreate[$name] < $this->max;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function hasItem(string $name): bool
	{
		if (isset($this->_items[$name])) {
			return !$this->_items[$name]->isEmpty();
		}
		return false;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function size(string $name): mixed
	{
        if (Coroutine::getCid() === -1) {
			return 0;
		}
		if (!isset($this->_items[$name])) {
			return 0;
		}
		return $this->_items[$name]->length();
	}


	/**
	 * @param $name
	 * @param $client
	 */
	public function push(string $name, mixed $client)
	{
        if (Coroutine::getCid() === -1) {
			return;
		}
		if (!isset($this->_items[$name])) {
			$this->_items[$name] = new Channel($this->max);
		}
		if (!$this->_items[$name]->isFull()) {
			$this->_items[$name]->push($client);
		}
		unset($client);
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clean(string $name)
	{
		if (Coroutine::getCid() === -1 || !isset($this->_items[$name])) {
			return;
		}
		$channel = $this->_items[$name];
		$this->pop($channel, $name, 0);
		if ($this->creates > -1) {
			Timer::clear($this->creates);
		}
		$this->_items[$name] = null;
	}


	/**
	 * @return Channel[]
	 */
	protected function getChannels(): array
	{
		return $this->_items;
	}


}
