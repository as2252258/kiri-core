<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Http;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Route\Annotation\Websocket;
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
			[$manager, $name] = $this->resolve($server, $fd);
			if (empty($manager) || !$manager->has($name)) {
				return;
			}
			$manager->runWith($name, [$fd]);
		} catch (\Throwable $exception) {
			$this->addError($exception);
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
	public function resolve($server, $fd)
	{
		if ($server instanceof WServer) {
			if (!$server->isEstablished($fd)) {
				return [null, null];
			}
			$manager = Snowflake::app()->annotation->websocket;
			$name = $manager->getName(AWebsocket::CLOSE);
		} else if ($server instanceof HServer) {
			$manager = Snowflake::app()->annotation->http;
			$name = $manager->getName(Http::CLOSE);
		} else {
			$manager = Snowflake::app()->annotation->tcp;
			$name = $manager->getName(Tcp::CLOSE);
		}
		return [$manager, $name];
	}


}
