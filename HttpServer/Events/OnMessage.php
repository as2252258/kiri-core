<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
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
		$event = Snowflake::app()->getEvent();
		Coroutine::defer(function () use ($event) {
			$event->trigger(Event::EVENT_AFTER_REQUEST);
		});
		try {
			if ($frame->opcode != 0x08) {
				$content = $this->resolve($event, $frame, $server);
				if (!empty($content)) {
					$server->send($frame->fd, $content);
				}
			}
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage(), 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		} finally {
			logger()->insert();
		}
	}

	/**
	 * @param $event
	 * @param $frame
	 * @param $server
	 * @return mixed
	 * @throws Exception
	 */
	private function resolve($event, $frame, $server): mixed
	{
		if ($event->exists(Event::SERVER_MESSAGE)) {
			$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
		} else {
			$frame->data = json_decode($frame->data, true);
		}
		if (empty($route = $frame->data['route'] ?? null)) {
			throw new Exception('Format error.');
		}
		$router = Snowflake::app()->getRouter();
		$node = $router->search('/' . Socket::MESSAGE . '::' . $route, 'sw::socket');
		if ($node === null) {
			throw new Exception('Page not found.');
		}
		return $node->dispatch($frame, $server);
	}

}
