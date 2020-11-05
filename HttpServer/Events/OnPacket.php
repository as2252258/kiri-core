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
use HttpServer\Events\Utility\DataResolve;

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
	 * @param Server $server
	 * @param $data
	 * @param $clientInfo
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, string $data, array $clientInfo)
	{
		try {
			$data = DataResolve::unpack($this->unpack, $clientInfo['address'], $clientInfo['port'], $data);
			if (empty($data)) {
				throw new Exception('Format error.');
			}
			$client = DataResolve::pack($this->pack, $data);
			return $server->sendto($clientInfo['address'], $clientInfo['port'], $client ?? 'Format error');
		} catch (\Throwable $exception) {
			$client = DataResolve::pack($this->pack, $exception->getMessage());

			var_dump($exception->getMessage());

			return $server->sendto($clientInfo['address'], $clientInfo['port'], $client ?? 'Format error');
		} finally {
			$event = Snowflake::app()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}


}
