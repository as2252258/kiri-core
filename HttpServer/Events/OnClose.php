<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Node;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;
use Exception;
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
		$this->execute($server, $fd);
		fire(Event::SYSTEM_RESOURCE_RELEASES);
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

			$node = $router->tree_search(explode('/', Socket::CLOSE . '::event'), 'sw::socket');

//			$node = $router->search('/' . Socket::CLOSE . '::event', 'sw::socket');
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
