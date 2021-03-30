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
			var_dump($data);
			$request = Request::createListenRequest($fd, $server, $data, $reID);
			var_dump($request);
			$router = Snowflake::app()->getRouter();
			if (($node = $router->find_path($request)) === null) {
				return $server->send($fd, Json::encode(['state' => 404]));
			}

			$dispatch = $node->dispatch();
			if (!is_string($dispatch)) $dispatch = Json::encode($dispatch);

			return $server->send($fd, $dispatch);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'receive');

			return $server->send($fd, Json::encode(['state' => 500, 'message' => $exception->getMessage()]));
		} finally {
			$event = Snowflake::app()->getEvent();
			$event->trigger(Event::SYSTEM_RESOURCE_RELEASES);
			logger()->insert();
		}
	}


}
