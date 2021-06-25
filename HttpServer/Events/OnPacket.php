<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Core\Json;
use Snowflake\Event;
use Swoole\Server;

/**
 * Class OnPacket
 * @package HttpServer\Events
 */
class OnPacket extends Callback
{

	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $client
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, string $data, array $client): mixed
	{
		try {
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

			$client['server_port'] = $client['port'];
			$name = $this->getName($client, Event::SERVER_RECEIVE);

			$result = Event::trigger($name, [$server, $data, $client]);
		} catch (\Throwable $exception) {
			$result = logger()->exception($exception);
		} finally {
			if (is_array($result) || is_object($result)) {
				$result = Json::encode($result);
			}
			$sendData = [$client['address'], $client['port'], $result];
			return $server->sendto(...$sendData);
		}
	}


}
