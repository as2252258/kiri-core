<?php

namespace Kiri\Pool;

use Kiri\Annotation\Inject;
use Kiri\Di\Context;
use Swoole\Coroutine\Channel;

class PoolItem
{


	/**
	 * @var PoolQueue
	 */
	private PoolQueue $_items;


	/**
	 * @var int
	 */
	private int $created = 0;


	/**
	 * @param int $maxCreated
	 * @param \Closure $callback
	 */
	public function __construct(readonly public int $maxCreated, readonly public \Closure $callback)
	{
		$this->_items = new PoolQueue($this->maxCreated);
	}


	/**
	 * @param PoolQueue $items
	 */
	public function setItems(PoolQueue $items): void
	{
		$this->_items = $items;
	}


	/**
	 * @param mixed $item
	 * @return void
	 */
	public function push(mixed $item): void
	{
		$this->_items->push($item);
	}


	/**
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->_items->isEmpty();
	}


	/**
	 * @return bool
	 */
	public function size(): bool
	{
		return $this->_items->length();
	}


	/**
	 * @return bool
	 */
	public function close(): bool
	{
		return $this->_items->close();
	}


	/**
	 * @param int $min
	 * @return void
	 */
	public function tailor(int $min = 0): void
	{
		while ($this->_items->length() > $min) {
			$connection = $this->_items->pop(0.000001);
			if ($connection instanceof StopHeartbeatCheck) {
				$connection->stopHeartbeatCheck();
			}
			$connection = null;
			$this->created -= 1;
		}
	}


	/**
	 * @param int $waite
	 * @return mixed
	 */
	public function pop(int $waite = 10): mixed
	{
		if ($this->created < $this->maxCreated) {
			$callback = $this->callback;
			$client = $callback();

			$this->created += 1;

			return $client;
		}
		return $this->_items->pop($waite);
	}
}
