<?php


namespace Kiri\Server\Contract;


use Swoole\Process;


/**
 * Interface BaseProcess
 * @package Contract
 */
interface OnProcessInterface
{

	/**
	 * @param Process $process
	 */
	public function onHandler(Process $process): void;


	/**
	 *
	 */
	public function onProcessStop(): void;


}
