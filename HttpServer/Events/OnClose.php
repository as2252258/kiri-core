<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\Socket;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;
use Swoole\WebSocket\Server as WebsocketServer;

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
			$this->execute($server, $fd);
		} catch (\Throwable $exception) {
			$this->addError($exception,'throwable');
		} finally {
			fire(Event::SYSTEM_RESOURCE_RELEASES);
			logger()->insert();
		}
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function execute(Server $server, int $fd): void
	{
		if (!$this->isWebsocket($server, $fd)) {
			$client = $server->getClientInfo($fd);
			fire($this->name($client['server_port']), [$server, $fd]);
		} else {
			$this->loadNode($server, $fd);
		}
	}


	/**
	 * @param $server_port
	 * @return string
	 */
	private function name($server_port): string
	{
		return 'listen ' . $server_port . ' ' . Event::SERVER_CLIENT_CLOSE;
	}


	/**
	 * @param $server
	 * @param $fd
	 * @return bool
	 */
	private function isWebsocket($server, $fd): bool
	{
		return $server instanceof WebsocketServer && $server->isEstablished($fd);
	}


	/**
	 * @param $server
	 * @param $fd
	 * @return mixed
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function loadNode($server, $fd): mixed
	{
		$query = Request::socketQuery((object)['fd' => $fd], Socket::CLOSE);
		if (($node = router()->find_path($query)) !== null) {
			return $node->dispatch($server, $fd);
		}
		return null;
	}
}
