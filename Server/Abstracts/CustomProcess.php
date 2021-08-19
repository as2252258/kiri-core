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


	/** @var Coroutine\Channel */
	protected Coroutine\Channel $channel;


	/**
	 * @param Process $process
	 */
	public function signListen(Process $process): void
	{
		$this->channel = new Coroutine\Channel(1);
		$this->channel->push(1);

		if (!$this->enableSwooleCoroutine) {
			Process::signal(SIGTERM | SIGKILL, function ($signo)
			use ($process) {
				putenv('processStatus=exit');

				$this->waiteExit($process);
			});
		} else {
			go(function () use ($process) {
				$data = Coroutine::waitSignal(SIGTERM | SIGKILL, -1);
				if ($data) {
					putenv('processStatus=exit');

					$this->waiteExit($process);
				}
			});
		}
	}


	/**
	 * @return string
	 */
	#[Pure] protected function getStatus(): string
	{
		return env('processStatus', 'working');
	}


	/**
	 * @return bool
	 */
	#[Pure] protected function isExit(): bool
	{
		return $this->getStatus() == 'exit';
	}


	/**
	 *
	 */
	protected function exit()
	{
		$this->channel->pop();
		$this->channel->close();
	}


	/**
	 * @return bool
	 */
	public function isWorking(): bool
	{
		return $this->channel->isEmpty();
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
