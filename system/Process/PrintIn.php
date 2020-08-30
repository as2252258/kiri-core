<?php


namespace Snowflake\Process;


class PrintIn extends Process
{


	public function onHandler(\Swoole\Process $process)
	{
		do {

			sleep(1);

		} while (true);
	}

}
