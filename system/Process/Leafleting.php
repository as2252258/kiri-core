<?php


namespace Snowflake\Process;


use Exception;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
		while (true) {
			var_dump($process->read());

			Coroutine::sleep(1);
		}
	}

}
