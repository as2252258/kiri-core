<?php

namespace Server\Abstracts;


use Swoole\Coroutine;
use Swoole\Process;

/**
 *
 */
abstract class CustomProcess implements \Server\SInterface\CustomProcess
{

	/** @var bool */
	protected bool $enableSwooleCoroutine = true;


	/**
	 * @param Process $process
	 */
	public function signListen(Process $process): void
	{
		if (!$this->enableSwooleCoroutine) {
			Process::signal(SIGTERM | SIGKILL, function ($signo)
			use ($process) {
				$this->waiteExit($process);
			});
		} else {
			go(function () use ($process) {
				$data = Coroutine::waitSignal(SIGTERM | SIGKILL, -1);
				if ($data) {
					$this->waiteExit($process);
				}
			});
		}
	}


	public function isWorking(): bool
	{
		return false;
	}


	/**
	 *
	 */
	private function waiteExit(Process $process): void
	{
		while ($this->isWorking()) {
			$this->sleep();
		}
		$process->exit(0);
	}


	/**
	 *
	 */
	private function sleep(): void
	{
		if ($this->enableSwooleCoroutine) {
			Coroutine::sleep(0.1);
		} else {
			usleep(100);
		}
	}

}
