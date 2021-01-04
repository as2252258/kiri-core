<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;

use HttpServer\Http\Context;
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
		$this->_items[$name] = new Channel($max);
		$this->max = $max;
	}

	/**
	 * @param $name
	 * @return array
	 * @throws Exception
	 */
	protected function get($name): array
	{
		[$timeout, $connection] = $this->_items[$name]->pop();
		if (!$this->checkCanUse($name, $timeout, $connection)) {
			unset($client);
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
	 */
	public function createConnect(array $config, string $coroutineName, callable $createHandler): void
	{
		if (Context::hasContext('create:connect:' . $coroutineName)) {
			return;
		}
		Context::setContext('create:connect:' . $coroutineName, 1);

		$client = call_user_func($createHandler, ...$config);

		$this->push($coroutineName, $client);

		Context::deleteId('create:connect:' . $coroutineName);
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function hasItem(string $name): bool
	{
		return $this->size($name) > 0;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function size(string $name): mixed
	{
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
		$this->_items[$name]->push([time(), $client]);
		unset($client);
	}


	/**
	 * @param string $name
	 */
	public function clean(string $name)
	{
		if (!isset($this->_items[$name])) {
			return;
		}
		$channel = $this->_items[$name];
		while ([$time, $client] = $channel->pop(0.001)) {
			unset($client);
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
