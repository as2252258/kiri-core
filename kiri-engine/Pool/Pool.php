<?php


namespace Kiri\Pool;


use Database\Mysql\PDO;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\CoordinatorManager;
use Kiri\Annotation\Inject;
use Kiri\Context;
use Kiri\Exception\ConfigException;
use Kiri\Server\Abstracts\StatusEnum;
use Kiri\Server\WorkerStatus;
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


	/**
	 * @var WorkerStatus
	 */
	#[Inject(WorkerStatus::class)]
	public WorkerStatus $status;


	use Alias;


	/**
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	public function flush($name, $retain_number)
	{
		$channel = $this->channel($name);
		$this->pop($channel, $retain_number);
	}


	/**
	 * @param PoolQueue $channel
	 * @param $retain_number
	 */
	protected function pop(PoolQueue $channel, $retain_number): void
	{
		while ($channel->length() > $retain_number) {
			$connection = $channel->pop(0.001);
			if ($connection instanceof StopHeartbeatCheck) {
				$connection->stopHeartbeatCheck();
			}
		}
	}


	/**
	 * @param $name
	 * @return array
	 * @throws ConfigException
	 */
	public function check($name): array
	{
		$channel = $this->channel($name);
		if ($channel->length() < 1) {
			return [0, 0];
		}

		if ($this->status->is(StatusEnum::EXIT)) {
			$channel->close();
			return [0, 0];
		}

		$success = 0;
		$lists = [];
		$count = $channel->length();
		while ($this->status->is(StatusEnum::EXIT) === false) {
			if (!(($pdo = $channel->pop(0.001)) instanceof PDO)) {
				break;
			}
			if ($pdo->check()) {
				$success += 1;
			}
			$lists[] = $pdo;
		}
		if ($this->status->is(StatusEnum::EXIT) === false) {
			foreach ($lists as $list) {
				$channel->push($list);
			}
		} else {
			$channel->close();
		}
		return [$count, $success];
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
		if (static::$_connections[$name]->isClose()) {
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
			return $channel->pop();
		}
		return $callback();
	}


	/**
	 * @param $channel
	 * @param $minx
	 * @return void
	 */
	protected function maxIdleQuantity($channel, $minx): void
	{
		if ($channel->length() > $minx) {
			$this->pop($channel, $minx);
		}
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
