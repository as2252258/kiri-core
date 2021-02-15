<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;

use HttpServer\Http\Context;
use PDO;
use Redis;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * Class Pool
 * @package Snowflake\Snowflake\Pool
 */
abstract class Pool extends Component
{

	/** @var Channel[] */
	private array $_items = [];

	protected int $max = 60;

	/**
	 * @param $name
	 * @param false $isMaster
	 * @param int $max
	 */
	public function initConnections($name, $isMaster = false, $max = 60)
	{
		$name = $this->name($name, $isMaster);
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
	 * @return array
	 * @throws Exception
	 */
	protected function get($name): array
	{
		if (!Context::inCoroutine()) {
			return [0, null];
		}
		[$timeout, $connection] = $this->_items[$name]->pop(-1);
		if (empty($timeout) || empty($connection)) {
			return [0, null];
		}
		if (!$this->checkCanUse($name, $timeout, $connection)) {
			return [0, null];
		} else {
			return [$timeout, $connection];
		}
	}



	/**
	 * @param $cds
	 * @param false $isMaster
	 * @return string
	 */
	public function name($cds, $isMaster = false): string
	{
		if ($isMaster === true) {
			return hash('sha256', $cds . 'master');
		} else {
			return hash('sha256', $cds . 'slave');
		}
	}


	/**
	 * @param $name
	 * @param $time
	 * @param $client
	 * @return mixed
	 * 检查连接可靠性
	 * @throws Exception
	 */
	public function checkCanUse(string $name, int $time, mixed $client): mixed
	{
		throw new Exception('Undefined system processing function.');
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
	 * @throws Exception
	 */
	public function getConnection(array $config, bool $isMaster)
	{
		throw new Exception('Undefined system processing function.');
	}


	/**
	 * @param array $config
	 * @param string $coroutineName
	 * @param callable $createHandler
	 * @return PDO|Redis|null
	 * @throws Exception
	 */
	public function createConnect(array $config, string $coroutineName, callable $createHandler): PDO|Redis|null
	{
		if (!$this->hasItem($coroutineName)) {
			return $this->get($coroutineName)[1];
		}
		if (Context::hasContext('create:connect:' . $coroutineName)) {
			return $this->get($coroutineName)[1];
		}
		Context::setContext('create:connect:' . $coroutineName, 1);

		$client = call_user_func($createHandler, ...$config);
		if (!Context::inCoroutine()) {
			Context::deleteId('create:connect:' . $coroutineName);
			return $client;
		}
		$this->push($coroutineName, $client);

		Context::deleteId('create:connect:' . $coroutineName);

		return $client;
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
		if (!$this->_items[$name]->isFull()) {
			$this->_items[$name]->push([time(), $client]);
		}
		unset($client);
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
		while ([$time, $client] = $channel->pop(0.001)) {
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
