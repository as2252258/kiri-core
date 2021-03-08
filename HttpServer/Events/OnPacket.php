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
		try {
			$request = Request::createListenRequest($clientInfo, $this->port, $server, $data);

			[$host, $port] = [$clientInfo['address'], $clientInfo['port']];

			$router = Snowflake::app()->getRouter();
			if (($node = $router->find_path($request)) === null) {
				return $server->sendto($host, $port, Json::encode(['state' => 404]));
			}
			return $server->sendto($host, $port, $node->dispatch());
		} catch (\Throwable $exception) {
			$response = Json::encode(['state' => 500, 'message' => $exception->getMessage()]);

			return $server->sendto($clientInfo['address'], $clientInfo['port'], $response);
		} finally {
			fire(Event::SYSTEM_RESOURCE_RELEASES);
			logger()->insert();
		}
	}


}
