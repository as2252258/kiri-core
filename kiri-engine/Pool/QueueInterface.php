<?php

namespace Kiri\Pool;

interface QueueInterface
{


	/**
	 * @return bool
	 */
	public function isEmpty(): bool;


	/**
	 * @param mixed $data
	 * @param float $timeout
	 * @return void
	 */
	public function push(mixed $data, float $timeout = -1): void;


	/**
	 * @param float $timeout
	 * @return mixed
	 */
	public function pop(float $timeout = -1): mixed;


	/**
	 * @return array
	 */
	public function stats(): array;


	/**
	 * @return bool
	 */
	public function close(): bool;


	/**
	 * @return int
	 */
	public function length(): int;


	/**
	 * @return bool
	 */
	public function isFull(): bool;


	/**
	 * @return bool
	 */
	public function isClose(): bool;

}
