<?php

namespace Snowflake\Events;

use SplPriorityQueue;

class EventListener
{

	/** @var SplPriorityQueue[] */
	private array $_events = [];


	/**
	 * @param $event
	 * @param EventDispatchInterface $handler
	 */
	public function on($event, EventDispatchInterface $handler)
	{
		if (!isset($this->_events[$event])) {
			$this->_events[$event] = new SplPriorityQueue();
		}
		$this->_events[$event]->insert($handler, $handler->getZOrder());
	}


	/**
	 * @param $event
	 * @return SplPriorityQueue|null
	 */
	public function getEventListeners($event): ?SplPriorityQueue
	{
		return $this->_events[$event] ?? null;
	}


}
