<?php

namespace Kiri\Task;

use Kiri\Server\Abstracts\BaseProcess;
use Swoole\Process;

class TaskProcess extends BaseProcess
{


	protected bool $enable_coroutine = false;


	public int $index = 0;


	/**
	 * @param int $index
	 */
	public function setIndex(int $index): void
	{
		$this->index = $index;
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return 'task.' . $this->index;
	}


	/**
	 * @param Process $process
	 * @throws \Exception
	 */
	public function process(Process $process): void
	{
		$task = \Kiri::getContainer()->get(OnServerTask::class);
		while (!$this->isStop()) {
			$read = $process->read();

			$task->onTask(null, 0, 0, $read);
		}
	}


	/**
	 * @return $this
	 */
	public function onSigterm(): static
	{
		pcntl_signal(SIGTERM, function () {
			$this->isStop = true;
		});
		return $this;
	}


}
