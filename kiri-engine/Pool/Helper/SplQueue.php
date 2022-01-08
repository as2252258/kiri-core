<?php

namespace Kiri\Pool\Helper;


use JetBrains\PhpStorm\Pure;

/**
 *
 */
class SplQueue implements QueueInterface
{

	private \SplQueue $channel;


	public int $errCode = 0;


	/**
	 * @param int $max
	 */
	#[Pure] public function __construct(public int $max)
	{
		$this->channel = new \SplQueue();
	}


	/**
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		// TODO: Implement isEmpty() method.
		return $this->channel->count() < 1;
	}


	/**
	 * @param mixed $data
	 * @param float $timeout
	 * @return bool
	 */
	public function push(mixed $data, float $timeout = -1): bool
	{
		// TODO: Implement push() method.
		$this->channel->enqueue($data);
		return true;
	}


	/**
	 * @param float $timeout
	 * @return mixed
	 */
	public function pop(float $timeout = -1): mixed
	{
		// TODO: Implement pop() method.
		return $this->channel->dequeue();
	}


	/**
	 * @return array
	 */
	public function stats(): array
	{
		// TODO: Implement stats() method.
		return [];
	}


	/**
	 * @return bool
	 */
	public function close(): bool
	{
		// TODO: Implement close() method.
		return false;
	}


	/**
	 * @return int
	 */
	public function length(): int
	{
		// TODO: Implement length() method.
		return $this->channel->count();
	}


	/**
	 * @return bool
	 */
	public function isFull(): bool
	{
		// TODO: Implement isFull() method.
		return $this->channel->count() >= $this->max;
	}
}
