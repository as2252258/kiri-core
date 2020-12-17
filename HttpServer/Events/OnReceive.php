<?php
declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;
use Closure;
use HttpServer\Events\Utility\DataResolve;

/**
 * Class OnReceive
 * @package HttpServer\Events
 */
class OnReceive extends Callback
{


	/** @var ?Closure */
	public ?Closure $unpack = null;


	/** @var ?Closure */
	public ?Closure $pack = null;


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reID
	 * @param string $data
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Server $server, int $fd, int $reID, string $data): mixed
	{
		try {
			$client = $server->getClientInfo($fd, $reID);

			$data = DataResolve::unpack($this->unpack, $client['remote_ip'], $client['remote_port'], $data);
			if (empty($data)) {
				throw new Exception('Format error.');
			}
			return $server->send($fd, DataResolve::pack($this->pack, $data));
		} catch (\Throwable $exception) {
			$response['message'] = $exception->getMessage();
			$response['state'] = 500;
			$response = DataResolve::pack($this->pack, $response);
			return $server->send($fd, $response);
		} finally {
			$event = Snowflake::app()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}

}
