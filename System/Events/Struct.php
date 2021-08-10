<?php

declare(strict_types=1);

namespace Kiri\Events;

/**
 *
 */
class Struct
{

	public string $event;

	public array|\Closure $listener;

	public int $priority;

	public function __construct(string $event, callable $listener, int $priority)
	{
		$this->event = $event;
		$this->listener = $listener;
		$this->priority = $priority;
	}
}
