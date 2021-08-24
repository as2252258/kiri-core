<?php

namespace Kiri\Events;

use Annotation\Inject;
use Kiri\Abstracts\BaseObject;


/**
 *
 */
class EventDispatch extends BaseObject
{

	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	/**
	 * @param object $triggerEvent
	 * @return object
	 */
	public function dispatch(object $triggerEvent): object
	{
		$lists = $this->eventProvider->getListenersForEvent($triggerEvent);
		foreach ($lists as $listener) {
			/** @var Struct $list */
			$listener($triggerEvent);
			if ($triggerEvent instanceof StoppableEventInterface && $triggerEvent->isPropagationStopped()) {
				break;
			}
		}
		return $triggerEvent;
	}


}
