<?php


namespace HttpServer\Events\Trigger;


use HttpServer\Events\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnManagerStop
 * @package HttpServer\Events\Trigger
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

		$events = Snowflake::get()->event;
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
