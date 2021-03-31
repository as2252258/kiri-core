<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;

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
			$clientInfo = $server->getClientInfo($fd);
			$event = Snowflake::app()->getEvent();

			if (!$event->exists(($name = $this->getName($clientInfo)))) {
				return;
			}
			$event->trigger($name, [$fd, $server]);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'throwable');
		} finally {
			fire(Event::SYSTEM_RESOURCE_RELEASES);
			logger()->insert();
		}
	}


	/**
	 * @param $server_port
	 * @return string
	 */
	private function getName($server_port): string
	{
		return 'listen ' . $server_port['server_port'] . ' ' . Event::SERVER_CLIENT_CLOSE;
	}

}
