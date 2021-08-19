<?php

namespace Server\Abstracts;


use JetBrains\PhpStorm\Pure;
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
		if (!$this->enableSwooleCoroutine) {
			Process::signal(SIGTERM | SIGKILL, function ($signo)
			use ($process) {
				$this->onProcessStop();
				$this->waiteExit($process);
			});
		} else {
			go(function () use ($process) {
				$data = Coroutine::waitSignal(SIGTERM | SIGKILL, -1);
				if ($data) {
					$this->onProcessStop();
					$this->waiteExit($process);
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
