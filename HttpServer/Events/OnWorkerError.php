<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
		$event->offName(Event::SERVER_WORKER_ERROR);

		$this->clear($server, $worker_id, self::EVENT_ERROR);
		if (!Config::has('email')) {
			return;
		}
		$this->system_mail(print_r([
			'$worker_pid' => $worker_pid,
			'$worker_id'  => $worker_id,
			'$exit_code'  => $exit_code,
			'$signal'     => $signal,
		], true));
	}

}
