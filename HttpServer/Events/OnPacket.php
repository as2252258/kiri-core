<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Closure;
use HttpServer\Abstracts\Callback;
use Snowflake\Core\JSON;
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


	/** @var ?Closure */
	public ?Closure $unpack = null;


	/** @var ?Closure */
	public ?Closure $pack = null;


	/**
	 * @param $data
	 * @return mixed
	 * @throws Exception
	 */
	public function pack($data)
	{
		$callback = $this->pack;
		if (is_callable($callback, true)) {
			return $callback($data);
		}
		return JSON::encode($data);
	}


	/**
	 * @param $data
	 * @return mixed
	 */
	public function unpack($data)
	{
		$callback = $this->unpack;
		if (is_callable($callback, true)) {
			return $callback($data);
		}
		return JSON::decode($data);
	}


	/**
	 * @param Server $server
	 * @param $data
	 * @param $clientInfo
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server,string $data,array $clientInfo)
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
			$event = Snowflake::app()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}


}
