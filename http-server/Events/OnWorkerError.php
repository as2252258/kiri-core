<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Swoole\Server;

class OnWorkerError extends Callback
{

	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 * @throws ConfigException
	 */
	public function onHandler(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
		$this->clear($server, $worker_id, self::EVENT_ERROR);
		if (!Config::has('email')) {
			return;
		}
		$email = Config::get('email');
		$name = Config::get('nickname', false, 'Admin');
		$this->system_mail($email, $name, print_r([
			'$worker_pid' => $worker_pid,
			'$worker_id'  => $worker_id,
			'$exit_code'  => $exit_code,
			'$signal'     => $signal,
		], true));
	}

}
