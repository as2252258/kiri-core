<?php


namespace HttpServer\Events;


use Exception;
use Snowflake\Core\JSON;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class Receive
 * @package HttpServer\Events
 */
class Receive extends Service
{

	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactorId
	 * @param string $data
	 * @return mixed
	 * @throws Exception
	 */
	public function onReceive(\Swoole\Server $server, int $fd, int $reactorId, string $data)
	{
		try {
			$client = [$fd];
			if (empty($data = $this->unpack($data))) {
				throw new Exception('Format error.');
			}
			$client[] = $this->pack($data);
			return $server->send(...$client);
		} catch (\Throwable $exception) {
			$client[] = $this->pack(['message' => $exception->getMessage()]);
			return $server->send(...$client);
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}

}
