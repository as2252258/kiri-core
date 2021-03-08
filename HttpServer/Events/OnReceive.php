<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use HttpServer\Http\Request;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;

/**
 * Class OnReceive
 * @package HttpServer\Events
 */
class OnReceive extends Callback
{

	public int $port = 0;


	public string $host = '';


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reID
	 * @param string $data
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $fd, int $reID, string $data): mixed
	{
		try {
			$request = Request::createListenRequest($fd, $this->port, $server, $data, $reID);

			$router = Snowflake::app()->getRouter();
			if (($node = $router->find_path($request)) === null) {
				return $server->send($fd, Json::encode(['state' => 404]));
			}
			return $server->send($fd, $node->dispatch());
		} catch (\Throwable $exception) {
			return $server->send($fd, Json::encode(['state' => 500, 'message' => $exception->getMessage()]));
		} finally {
			$event = Snowflake::app()->getEvent();
			$event->trigger(Event::SYSTEM_RESOURCE_RELEASES);
			logger()->insert();
		}
	}


}
