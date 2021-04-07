<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerError
 * @package HttpServer\Events
 */
class OnWorkerError extends Callback
{


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_WORKER_ERROR);

		$message = sprintf('Worker#%d error stop. signal %d, exit_code %d',
			$worker_id, $signal, $exit_code
		);

		write($message, 'worker-exit');

		\logger()->insert();
	}

}
