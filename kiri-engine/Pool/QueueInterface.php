<?php

namespace Kiri\Pool;

interface QueueInterface
{


	public function isEmpty(): bool;

	public function push(mixed $data, float $timeout = -1): bool;


	public function pop(float $timeout = -1): mixed;


	public function stats(): array;


	public function close(): bool;


	public function length(): int;


	public function isFull(): bool;


	public function isClose(): bool;

}
