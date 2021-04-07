<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPacket
 * @package HttpServer\Events
 */
class OnPacket extends Callback
{


	public int $port = 0;


	public string $host = '';


	/**
	 * @param Server $server
	 * @param $data
	 * @param $clientInfo
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, string $data, array $clientInfo): mixed
	{
		[$host, $port] = [$clientInfo['address'], $clientInfo['port']];
		try {
			\Swoole\Coroutine\defer(function () {
				fire(Event::SYSTEM_RESOURCE_RELEASES);
				\logger_insert();
			});
			$request = $this->_request($clientInfo, $server, $data);

			$router = Snowflake::app()->getRouter();
			if (($node = $router->find_path($request)) === null) {
				return $server->sendto($host, $port, Json::encode(['state' => 404]));
			}

			$dispatch = $node->dispatch();
			if (!is_string($dispatch)) $dispatch = Json::encode($dispatch);
			if (empty($dispatch)) {
				$dispatch = Json::encode(['state' => 0, 'message' => 'ok']);
			}
			return $server->sendto($host, $port, $dispatch);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'packet');

			$response = Json::encode(['state' => 500, 'message' => $exception->getMessage()]);

			return $server->sendto($host, $port, $response);
		}
	}


}
