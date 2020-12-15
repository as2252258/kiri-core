<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Http;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Route\Annotation\Websocket;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use HttpServer\Route\Node;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
		Coroutine::defer(function () {
			fire(Event::EVENT_AFTER_REQUEST);
		});
		$this->execute($server, $fd);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function execute(Server $server, int $fd): void
	{
		try {
			$router = Snowflake::app()->getRouter();
			if (!($server instanceof WServer) || !$server->isEstablished($fd)) {
				return;
			}
			$node = $router->search('/' . Socket::CLOSE . '::event', 'sw::socket');
			if ($node instanceof Node) {
				$node->dispatch($server, $fd);
			}
		} catch (\Throwable $exception) {
			$this->addError($exception);
		} finally {
			$logger = Snowflake::app()->getLogger();
			$logger->insert();
		}
	}
}
