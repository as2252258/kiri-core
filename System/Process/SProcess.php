<?php


namespace Snowflake\Process;


interface SProcess
{


	/**
	 * @param \Swoole\Process $process
	 */
	public function onHandler(\Swoole\Process $process): void;

}
