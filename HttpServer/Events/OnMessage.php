<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class OnMessage
 * @package HttpServer\Events
 */
class OnMessage extends Callback
{

	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @throws
	 */
	public function onHandler(Server $server, Frame $frame)
	{
		try {
			if ($frame->opcode == 0x08) {
				return;
			}
			$event = Snowflake::app()->event;
			if ($event->exists(Event::SERVER_MESSAGE)) {
				$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
			} else {
				$frame->data = json_decode($frame->data, true);
			}

			$manager = Snowflake::app()->annotation->websocket;
			if (!isset($frame->data['route'])) {
				throw new \Exception('Fromat errr.');
			}
			$events = $manager->getName(AWebsocket::MESSAGE, [null, null, $frame->data['route']]);
			$manager->runWith($events, [$frame, $server]);
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage(), 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		} finally {
			$event = Snowflake::app()->event;
			$event->trigger(Event::EVENT_AFTER_REQUEST);
			Snowflake::app()->logger->insert();
		}
	}


}
