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
	public function onHandler(\Swoole\Server $server, int $fd, int $reID, string $data)
	{
		try {
			$client = [$fd];
			$data = DataResolve::unpack($this->unpack, null, null, $data);
			if (empty($data)) {
				throw new Exception('Format error.');
			}
			$client[] = DataResolve::pack($this->pack, $data);
			return $server->send(...$client);
		} catch (\Throwable $exception) {
			$client[] = DataResolve::pack($this->pack, ['message' => $exception->getMessage()]);
			return $server->send(...$client);
		} finally {
			$event = Snowflake::app()->event;
			$event->trigger(Event::SERVER_WORKER_STOP);
		}
	}

}
