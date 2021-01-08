<?php


namespace Snowflake\Process;


use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Timer;

/**
 * Class Biomonitoring
 * @package components
 */
class Biomonitoring extends Process
{


	/**
	 * @param \Swoole\Process $process
	 * @throws ComponentException
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		$server = Snowflake::app()->getService();
		Timer::tick(1000, function () use ($server) {
			clearstatcache();
			if (($size = filesize($server->setting['log_file'])) > 1024000000) {
				@unlink($server->setting['log_file']);
				Process::kill($server->master_pid, SIGRTMIN);
			}
		});
	}

}
