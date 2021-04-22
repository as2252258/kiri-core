<?php


namespace Snowflake\Process;


interface SProcess
{


//    public function getProcessName(): string;
//
//
//    public function before(\Swoole\Process $process): void;

    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void;

}
