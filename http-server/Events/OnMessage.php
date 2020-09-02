<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;
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

			$event = Snowflake::get()->event;
			if ($event->exists(Event::SERVER_MESSAGE)) {
				$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
			} else {
				$frame->data = json_decode($frame->data, true);
			}

			/** @var AWebsocket $manager */
			$manager = Snowflake::get()->annotation->get('websocket');
			$manager->runWith($manager->getName(AWebsocket::MESSAGE, [null, null, $frame->data['route']]), [$frame, $server]);
		} catch (\Exception $exception) {
			$this->addError($exception->getMessage(), 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::EVENT_AFTER_REQUEST);
			Snowflake::get()->logger->insert();
		}
	}




}
