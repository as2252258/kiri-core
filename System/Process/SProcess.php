<?php


namespace Snowflake\Process;


interface SProcess
{


	public function onHandler(\Swoole\Process $process);

}
