<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
use Kiri\Di\Context;
use Swoole\Coroutine\Channel;

class PoolItem
{


	/**
	 * @var Channel|SplQueue
	 */
	private Channel|SplQueue $_items;


	/**
	 * @var int
	 */
	private int $created = 0;


	/**
	 * @param int $maxCreated
	 * @param Closure $callback
	 */
	public function __construct(readonly public int $maxCreated, readonly public Closure $callback)
	{
		if (Context::inCoroutine()) {
			$this->_items = new Channel($this->maxCreated);
		} else {
			$this->_items = new SplQueue($this->maxCreated);
		}
	}


	/**
	 * @param Channel|SplQueue $items
	 */
	public function setItems(Channel|SplQueue $items): void
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
		if ($this->_items->isEmpty()) {
			return call_user_func($this->callback);
		} else {
			return $this->_items->pop();
		}
	}
}
