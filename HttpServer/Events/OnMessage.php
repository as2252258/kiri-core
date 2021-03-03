<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
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
			Coroutine::defer(function () {
				fire(Event::SYSTEM_RESOURCE_RELEASES);
				Snowflake::app()->getLogger()->insert();
			});
			if ($frame->opcode == 0x08) {
				return;
			}
			$event = Snowflake::app()->getEvent();
			$content = $this->resolve($event, $frame, $server);
			if (!empty($content)) {
				$server->send($frame->fd, $content);
			}
		} catch (\Throwable $exception) {
			$this->addError($exception, 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		}
	}

	/**
	 * @param $event
	 * @param Frame $frame
	 * @param $server
	 * @return mixed
	 * @throws Exception
	 */
	private function resolve($event, Frame $frame, $server): mixed
	{
		if ($event->exists(Event::SERVER_MESSAGE)) {
			$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
		} else {
			$frame->data = json_decode($frame->data, true);
		}
		if (!empty($route = $frame->data['route'] ?? null)) {
			return $this->loadNode($frame, $route, $server);
		}
		return null;
	}


	/**
	 * @param $frame
	 * @param $route
	 * @param $server
	 * @return mixed
	 * @throws ComponentException
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function loadNode($frame, $route, $server): mixed
	{
		$query = Request::socketQuery($frame, Socket::MESSAGE, $route);
		if (($node = router()->find_path($query)) !== null) {
			return $node->dispatch($frame, $server);
		}
		return null;
	}

}
