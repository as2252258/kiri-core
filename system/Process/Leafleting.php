<?php


namespace Snowflake\Process;


use Exception;
use Snowflake\Snowflake;
use Swoole\Timer;

/**
 * Class Logfilemonitoring
 */
class Leafleting extends Process
{

	/**
	 * @param \Swoole\Process $process
	 * @return mixed|void
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process)
	{
		Timer::tick(1000, function () use ($process)  {
			var_dump($process->read());
		});
	}

}
