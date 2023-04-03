<?php


namespace Kiri\Pool;


use Database\Mysql\PDO;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Inject;
use Kiri\Di\ContainerInterface;
use Kiri\Exception\ConfigException;
use Kiri\Server\Abstracts\StatusEnum;
use Kiri\Server\WorkerStatus;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool extends Component
{

	/** @var array<PoolItem> */
	private array $_connections = [];

	/**
	 * @var WorkerStatus
	 */
	#[Inject(WorkerStatus::class)]
	public WorkerStatus $status;

	/**
	 * @var ContainerInterface
	 */
	#[Inject(ContainerInterface::class)]
	public ContainerInterface $container;


	/**
	 * @param $name
	 * @param $retain_number
	 * @throws Exception
	 */
	public function flush($name, $retain_number)
	{
		if ($this->hasChannel($name)) {
			$channel = $this->channel($name);
			$channel->tailor($retain_number);
		}
	}


	/**
	 * @param PoolItem $channel
	 * @param $retain_number
	 */
	protected function pop(PoolItem $channel, $retain_number): void
	{
		$channel->tailor($retain_number);
	}


	/**
	 * @param $name
	 * @return array
	 */
	public function check($name): array
	{
//		$channel = $this->channel($name);
//		if ($channel->size() < 1) {
//			return [0, 0];
//		}
//
//		if ($this->status->is(StatusEnum::EXIT)) {
//			$channel->close();
//			return [0, 0];
//		}
//
//		$success = 0;
//		$lists = [];
//		$count = $channel->size();
//		while ($this->status->is(StatusEnum::EXIT) === false) {
//			if (!(($pdo = $channel->pop(0.001)) instanceof PDO)) {
//				break;
//			}
//			if ($pdo->check()) {
//				$success += 1;
//			}
//			$lists[] = $pdo;
//		}
//		if ($this->status->is(StatusEnum::EXIT) === false) {
//			foreach ($lists as $list) {
//				$channel->push($list);
//			}
//		} else {
//			$channel->close();
//		}
//		return [$count, $success];
		return [0, 0];
	}


	/**
	 * @param $name
	 * @param int $max
	 * @param \Closure $closure
	 */
	public function initConnections($name, int $max, \Closure $closure)
	{
		if (!isset($this->_connections[$name])) {
			$this->_connections[$name] = new PoolItem($max, $closure);
		}
	}


	/**
	 * @param $name
	 * @return PoolItem
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function channel($name): PoolItem
	{
		if (!isset($this->_connections[$name])) {
			throw new Exception('Channel is not exists.');
		}
		return $this->_connections[$name];
	}


	public function hasChannel($name): bool
	{
		if (!isset($this->_connections[$name])) {
			return false;
		}
		return true;
	}


	/**
	 * @param string $name
	 * @return array
	 * @throws ConfigException
	 */
	public function get(string $name): mixed
	{
		return $this->channel($name)->pop();
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
		$channel = $this->_connections[$name] ?? null;
		if ($channel === null) {
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
		$channel = $this->_connections[$name] ?? null;
		if ($channel === null) {
			return 0;
		}
		return $channel->size();
	}


	/**
	 * @param string $name
	 * @param mixed $client
	 * @throws ConfigException
	 */
	public function push(string $name, mixed $client)
	{
		$this->channel($name)->push($client);
	}


	/**
	 * @param $name
	 * @param int $time
	 * @return array
	 * @throws ConfigException
	 */
	public function waite($name, int $time = 30): mixed
	{
		return $this->channel($name)->pop($time);
	}


	/**
	 * @param string $name
	 * @throws Exception
	 */
	public function clean(string $name)
	{
		$channel = $this->_connections[$name] ?? null;
		if ($channel === null) {
			return;
		}
		$channel->tailor(0);
		$channel->close();
	}


	/**
	 * @return PoolQueue[]
	 */
	protected function channels(): array
	{
		return $this->_connections;
	}


}
