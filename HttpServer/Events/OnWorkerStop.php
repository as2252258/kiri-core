<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Swoole\Timer;

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
		Event::trigger(Event::SERVER_WORKER_STOP);

		fire(Event::SYSTEM_RESOURCE_CLEAN);

		Timer::clearAll();
	}

}
