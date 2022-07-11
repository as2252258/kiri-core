<?php

namespace Kiri\Pool;

use Kiri\Context;

use Swoole\Coroutine\Channel;

class PoolQueue implements QueueInterface
{

	private Channel|SplQueue $queue;


	/**
	 * @param int $max
	 */
	public function __construct(public int $max)
	{
		if (Context::inCoroutine()) {
			$this->queue = new Channel($this->max);
		} else {
			$this->queue = new SplQueue($this->max);
		}
	}


	/**
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->queue->isEmpty();
	}

	/**
	 * @param mixed $data
	 * @param float $timeout
	 * @return bool
	 */
	public function push(mixed $data, float $timeout = -1): bool
	{
		if (!$this->isClose()) {
			return $this->queue->push($data, $timeout);
		}
		return false;
	}


	/**
	 * @param float $timeout
	 * @return mixed
	 */
	public function pop(float $timeout = 0): mixed
	{
		return $this->queue->pop($timeout);
	}


	/**
	 * @return array
	 */
	public function stats(): array
	{
		return $this->queue->stats();
	}

	/**
	 * @return bool
	 */
	public function close(): bool
	{
		return $this->queue->close();
	}


	/**
	 * @return int
	 */
	public function length(): int
	{
		return $this->queue->length();
	}


	/**
	 * @return bool
	 */
	public function isFull(): bool
	{
		return $this->queue->isFull();
	}


	/**
	 * @return bool
	 */
	public function isClose(): bool
	{
		if ($this->queue instanceof Channel) {
			return $this->queue->errCode == SWOOLE_CHANNEL_CLOSED;
		}
		return false;
	}

}
