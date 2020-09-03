<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnManagerStop
 * @package HttpServer\Events
 */
class OnManagerStop extends Callback
{

	/**
	 * @param $server
	 * @throws \Exception
	 */
	public function onHandler(Server $server)
	{
		$this->warning('manager stop.');

		$events = Snowflake::app()->event;
		if ($events->exists(Event::SERVER_MANAGER_STOP)) {
			$events->trigger(Event::SERVER_MANAGER_STOP, [$server]);
		}

//		$runPath = storage(null, 'workerIds');
//		foreach (glob($runPath . '/*') as $item) {
//			if (!file_exists($item)) {
//				continue;
//			}
//			@unlink($item);
//		}
	}

}
