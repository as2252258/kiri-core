<?php


namespace Server\SInterface;


use Swoole\Process;


/**
 * Interface CustomProcess
 * @package SInterface
 */
interface CustomProcess
{


	/**
	 * @param Process $process
	 * @return string
	 */
	public function getProcessName(Process $process): string;


	/**
	 * @param Process $process
	 */
	public function onHandler(Process $process): void;



}
