<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Context;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
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
			if ($frame->opcode != 0x08) {
				$event = Snowflake::app()->getEvent();
				$content = $this->resolve($event, $frame, $server);
				if (!empty($content)) {
					$server->send($frame->fd, $content);
				}
			}
		} catch (\Throwable $exception) {
			$this->addError($exception, 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		} finally {
			fire(Event::SYSTEM_RESOURCE_RELEASES);
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
		$context = Config::get('router', false, ROUTER_TREE);
		if ($context === ROUTER_TREE) {
			$node = $router->tree_search(explode('/', Socket::MESSAGE . '::' . $route), 'sw::socket');
		} else {
			$node = $router->search('/' . Socket::HANDSHAKE . '::' . $route, 'sw::socket');
		}
		if ($node === null) {
			throw new Exception('Page not found.');
		}
		return $node->dispatch($frame, $server);
	}

}
