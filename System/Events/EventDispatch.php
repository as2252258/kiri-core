<?php

namespace Snowflake\Events;

class EventDispatch
{

	private EventListener $eventListener;


	/**
	 * @param $event
	 * @param array $params
	 */
	public function emit($event, array $params = [])
	{
		$events = $this->eventListener->getEventListeners($event);
		if (empty($events)) {
			return;
		}
		while ($events->valid()) {
			/** @var EventDispatchInterface $interface */
			$interface = $events->current();
			$interface->onHandler();
			if ($interface->stopPagination()) {
				break;
			}
			$events->next();
		}
	}

}
