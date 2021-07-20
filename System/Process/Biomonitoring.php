<?php


namespace Snowflake\Process;


use Exception;
use JetBrains\PhpStorm\Pure;
use Server\SInterface\CustomProcess;
use Snowflake\Snowflake;
use Swoole\Timer;

/**
 * Class Biomonitoring
 * @package components
 */
class Biomonitoring implements CustomProcess
{


	/**
	 * @param \Swoole\Process $process
	 * @return string
	 */
	#[Pure] public function getProcessName(\Swoole\Process $process): string
	{
		// TODO: Implement getProcessName() method.
		return get_called_class();
	}


	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		$server = Snowflake::app()->getSwoole();
		Timer::tick(1000, function () use ($server) {
			clearstatcache();
			if (filesize($server->setting['log_file']) > 1024000000) {
				@unlink($server->setting['log_file']);
				Process::kill($server->master_pid, SIGRTMIN);
			}
		});
	}

}
