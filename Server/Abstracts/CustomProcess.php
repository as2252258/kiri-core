<?php

namespace Server\Abstracts;


use JetBrains\PhpStorm\Pure;
use Kiri\Kiri;
use Swoole\Coroutine;
use Swoole\Process;

/**
 *
 */
abstract class CustomProcess implements \Server\SInterface\CustomProcess
{

	/** @var bool */
	protected bool $enableSwooleCoroutine = true;


	protected bool $isStop = false;


	/**
	 *
	 */
	public function onProcessStop(): void
	{
		$this->isStop = true;
	}


	/**
	 * @return bool
	 */
	public function checkProcessIsStop(): bool
	{
		return $this->isStop === true;
	}


	/**
	 * @param Process $process
	 */
	public function signListen(Process $process): void
	{
		if (Coroutine::getCid() === -1) {
			Process::signal(SIGTERM | SIGKILL, function ($signo) use ($process) {
				if ($signo) {
					$lists = Kiri::app()->getProcess();
					foreach ($lists as $process) {
						$process->exit(0);
					}
				}
			});
		} else {
			go(function () use ($process) {
				$data = Coroutine::waitSignal(SIGTERM | SIGKILL, -1);
				if ($data) {
					$lists = Kiri::app()->getProcess();
					foreach ($lists as $process) {
						$process->exit(0);
					}
				}
			});
		}
	}


	/**
	 *
	 */
	protected function exit(): void
	{
		putenv('process.status=idle');
	}


	/**
	 * @return bool
	 */
	#[Pure] public function isWorking(): bool
	{
		return env('process.status', 'working') == 'working';
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
