<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class OnWorkerExit
 * @package HttpServer\Events
 */
class OnWorkerExit extends Callback
{

	/**
	 * @param $server
	 * @param $worker_id
	 * @throws Exception
	 */
	public function onHandler($server, $worker_id)
	{
		putenv('state=exit');

		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_WORKER_EXIT);
		$event->offName(Event::SERVER_WORKER_EXIT);

		$this->clear($server, $worker_id, self::EVENT_EXIT);
	}

}
