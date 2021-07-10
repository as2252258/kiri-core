<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Swoole\Server;

/**
 * Class OnClose
 * @package HttpServer\Events
 *
 */
class OnClose extends Callback
{



	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $fd)
	{
		try {
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

			Event::trigger(Event::SERVER_ON_CLOSE, [$server, $fd]);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'throwable');
		}
	}

}
