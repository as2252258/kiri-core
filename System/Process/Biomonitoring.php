<?php


namespace Kiri\Process;


use Exception;
use JetBrains\PhpStorm\Pure;
use Server\SInterface\CustomProcess;
use Kiri\Kiri;
use Swoole\Timer;
use Swoole\Process;

/**
 * Class Biomonitoring
 * @package components
 */
class Biomonitoring implements CustomProcess
{


	/**
	 * @param Process $process
	 * @return string
	 */
	#[Pure] public function getProcessName(Process $process): string
	{
		// TODO: Implement getProcessName() method.
		return get_called_class();
	}


	/**
	 * @param Process $process
	 * @throws Exception
	 */
	public function onHandler(Process $process): void
	{
		$server = Kiri::app()->getSwoole();
		Timer::tick(1000, function () use ($server) {
			clearstatcache();
			if (filesize($server->setting['log_file']) > 1024000000) {
				@unlink($server->setting['log_file']);
				Process::kill($server->master_pid, SIGRTMIN);
			}
		});
	}

}
