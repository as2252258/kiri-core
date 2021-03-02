<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class OnWorkerStop
 * @package HttpServer\Events
 */
class OnWorkerStop extends Callback
{


	/**
	 * @param $server
	 * @param $worker_id
	 * @throws Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_WORKER_STOP);
		$event->offName(Event::SERVER_WORKER_STOP);

		$this->clear($server, $worker_id, self::EVENT_STOP);
	}

}
