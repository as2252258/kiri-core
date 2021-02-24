<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;

use HttpServer\Http\Context;
use JetBrains\PhpStorm\Pure;
use PDO;
use Redis;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Pool\Timeout;
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


	/**
	 * @return array
	 * @throws ConfigException
	 */
	private function getClearTime(): array
	{
		$firstClear = Config::get('pool.clear.start', false, 600);
		$lastClear = Config::get('pool.clear.end', false, 300);
		return [$firstClear, $lastClear];
	}


	/**
	 * @throws Exception
	 */
	public function Heartbeat_detection()
	{
		if ($this->lastTime == 0) {
			return;
		}
		[$firstClear, $lastClear] = $this->getClearTime();
		if ($this->lastTime + $firstClear < time()) {
			$this->flush(0);
		} else if ($this->lastTime + $lastClear < time()) {
			$this->flush(2);
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
			$this->debug('release Timer::tick');
			Timer::clear($this->creates);
			$this->creates = -1;
		}
	}


	/**
	 * @param $channel
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	protected function pop($channel, $name, $retain_number)
	{
		while ($channel->length() > $retain_number) {
			$connection = $channel->pop();
			if ($connection) {
				unset($connection);
			}
			$this->desc($name);
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
		if (!Context::inCoroutine()) {
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
		if (!Context::inCoroutine()) {
			return $this->createClient($name, $callback);
		}
		if (!$this->hasItem($name)) {
			$this->createByCallback($name, $callback);
		}
		$connection = $this->_items[$name]->pop(-1);
		if (!$this->checkCanUse($name, $connection)) {
			return $this->createClient($name, $callback);
		} else {
			return $connection;
		}
	}


	/**
	 * @param $name
	 * @param mixed $callback
	 */
	private function createByCallback($name, mixed $callback)
	{
		if ($this->creates === -1 && !is_callable($callback)) {
			$this->creates = Timer::tick(1000, [$this, 'Heartbeat_detection']);
		}
		if (!Context::hasContext('create::client::ing::' . $name)) {
			$this->push($name, $this->createClient($name, $callback));
			Context::deleteContext('create::client::ing::' . $name);
		}
	}


	/**
	 * @param $cds
	 * @param $coroutineName
	 * @param false $isBefore
	 * @throws ComponentException
	 */
	public function printClients($cds, $coroutineName, $isBefore = false)
	{
		$this->warning(($isBefore ? 'before ' : '') . 'create client[address: ' . $cds . ', ' . env('workerId') . ', coroutine: ' . Coroutine::getCid() . ', has num: ' . $this->size($coroutineName) . ', has create: ' . $this->_create . ']');
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
	 * @return mixed
	 * 检查连接可靠性
	 */
	public function checkCanUse(string $name, mixed $client): mixed
	{
		return true;
	}

	/**
	 * @param $name
	 * @throws Exception
	 */
	public function desc(string $name)
	{
		throw new Exception('Undefined system processing function.');
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
		if (!Context::inCoroutine()) {
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
		if (!Context::inCoroutine()) {
			return;
		}
		if (!isset($this->_items[$name])) {
			$this->_items[$name] = new Channel($this->max);
		}
		if (!$this->_items[$name]->isFull()) {
			$this->_items[$name]->push($client);
		}
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clean(string $name)
	{
		if (!Context::inCoroutine()) {
			return;
		}
		if (!isset($this->_items[$name])) {
			return;
		}
		$channel = $this->_items[$name];
		while ($client = $channel->pop(0.001)) {
			unset($client);
			$this->desc($name);
		}
	}


	/**
	 * @return Channel[]
	 */
	protected function getChannels(): array
	{
		return $this->_items;
	}


}
