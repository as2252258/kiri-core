<?php


namespace Snowflake\Process;


interface SProcess
{


	/**
	 * @return string
	 *
	 * return process name
	 */
    public function getProcessName(): string;


	/**
	 * @param \Swoole\Process $process
	 */
    public function before(\Swoole\Process $process): void;

    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void;

}
