<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
			$event = Snowflake::app()->getEvent();
			Coroutine::defer(function () use ($event) {
				$event->trigger(Event::EVENT_AFTER_REQUEST);
				logger()->insert();
			});
			$this->resolve($event, $frame, $server);
			$manager = Snowflake::app()->getAnnotation()->websocket;
			$manager->runWith($this->name($manager, $frame), [$frame, $server]);
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage(), 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		}
	}

	/**
	 * @param $event
	 * @param $frame
	 * @param $server
	 * @throws Exception
	 */
	private function resolve($event, $frame, $server)
	{
		if ($event->exists(Event::SERVER_MESSAGE)) {
			$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
		} else {
			$frame->data = json_decode($frame->data, true);
		}
		if (!isset($frame->data['route'])) {
			throw new Exception('Format error.');
		}
	}


	/**
	 * @param $manager
	 * @param $frame
	 * @return mixed
	 */
	private function name($manager, $frame)
	{
		return $manager->getName(AWebsocket::MESSAGE, [null, null, $frame->data['route']]);
	}


}
