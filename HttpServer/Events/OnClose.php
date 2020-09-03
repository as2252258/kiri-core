<?php


namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Annotation;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;
use Swoole\Http\Server as HServer;
use Swoole\WebSocket\Server as WServer;

/**
 * Class OnClose
 * @package HttpServer\Events
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
			[$manager, $name] = $this->resovle($server, $fd);
			if ($manager !== null && !$manager->has($name)) {
				$manager->runWith($name, [$fd]);
			}
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage());
		} finally {
			$event = Snowflake::app()->event;
			$event->trigger(Event::RELEASE_ALL);

			$logger = Snowflake::app()->getLogger();
			$logger->insert();
		}
	}


	/**
	 * @param $server
	 * @param $fd
	 * @return array|null
	 * @throws Exception
	 */
	public function resovle($server, $fd)
	{
		if ($server instanceof WServer) {
			if (!$server->isEstablished($fd)) {
				return [null, null];
			}
			$manager = Snowflake::app()->annotation->get('websocket');
			$name = $manager->getName(AWebsocket::CLOSE);
		} else if ($server instanceof HServer) {
			$manager = Snowflake::app()->annotation->get('http');
			$name = $manager->getName(Annotation::CLOSE);
		} else {
			$manager = Snowflake::app()->annotation->get('tcp');
			$name = $manager->getName(Tcp::CLOSE);
		}
		return [$manager, $name];
	}


}
