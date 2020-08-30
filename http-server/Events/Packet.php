<?php


namespace HttpServer\Events;


use Exception;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPacket
 * @package HttpServer\Events\Trigger
 */
class Packet extends Service
{

	/**
	 * @param Server $server
	 * @param $data
	 * @param $clientInfo
	 * @return mixed
	 * @throws
	 */
	public function onHandler($server, $data, $clientInfo)
	{
		try {
			$client = [$clientInfo['address'], $clientInfo['port']];
			if (empty($data = $this->unpack($data))) {
				throw new Exception('Format error.');
			}
			$client[] = $this->pack($data);
			return $server->sendto(...$client);
		} catch (\Throwable $exception) {
			$client[] = $this->pack(['message' => $exception->getMessage()]);
			return $server->sendto(...$client);
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}

}
