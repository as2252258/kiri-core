<?php


namespace Kiri\Pool;


use Database\Mysql\PDO;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Inject;
use Kiri\Exception\ConfigException;
use Kiri\Server\Abstracts\StatusEnum;
use Kiri\Server\WorkerStatus;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool extends Component
{

	/** @var array<PoolQueue> */
	private static array $_connections = [];

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
		if ($this->hasChannel($name)) {
			$this->pop($this->channel($name), $retain_number);
		}
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
	 */
	public function initConnections($name, int $max = 60)
	{
		$channel = static::$_connections[$name] ?? null;
		if (($channel instanceof PoolQueue) && !$channel->isClose()) {
			return;
		}
		static::$_connections[$name] = new PoolQueue($max);
	}


	/**
	 * @param $name
	 * @return PoolQueue
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function channel($name): PoolQueue
	{
		$channel = static::$_connections[$name] ?? null;
		if (!($channel instanceof PoolQueue)) {
			throw new Exception('Channel is not exists.');
		}
		if ($channel->isClose()) {
			throw new Exception('Channel is Close.');
		}
		return $channel;
	}


	public function hasChannel($name): bool
	{
		$channel = static::$_connections[$name] ?? null;
		if (!($channel instanceof PoolQueue)) {
			return false;
		}
		if ($channel->isClose()) {
			return false;
		}
		return true;
	}


	/**
	 * @param $name
	 * @param $callback
	 * @return array
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get($name, $callback): mixed
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
		$channel = static::$_connections[$name] ?? null;
		if (!($channel instanceof PoolQueue) || $channel->isClose()) {
			return false;
		}
		return !$channel->isEmpty();
	}


	/**
	 * @param string $name
	 * @return int
	 */
	public function size(string $name): int
	{
		$channel = static::$_connections[$name] ?? null;
		if (!($channel instanceof PoolQueue) || $channel->isClose()) {
			return 0;
		}
		return $channel->length();
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
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clean(string $name)
	{
		$channel = static::$_connections[$name] ?? null;
		if (!($channel instanceof PoolQueue) || $channel->isClose()) {
			return;
		}
		while ($channel->length() > 0) {
			$client = $channel->pop();
			if ($client instanceof StopHeartbeatCheck) {
				$client->stopHeartbeatCheck();
			}
		}
		$channel->close();
	}


	/**
	 * @return PoolQueue[]
	 */
	protected function channels(): array
	{
		return static::$_connections;
	}


}
