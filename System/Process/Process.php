<?php
declare(strict_types=1);


namespace Kiri\Process;


use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Event;
use Kiri\Kiri;

/**
 * Class Process
 * @package Kiri\Kiri\Service
 */
abstract class Process extends \Swoole\Process implements SProcess
{


	/**
	 * Process constructor.
	 * @param $application
	 * @param $name
	 * @param bool $enable_coroutine
	 * @throws Exception
	 */
	public function __construct($application, $name, $enable_coroutine = true)
	{
		parent::__construct([$this, '_load'], false, 1, $enable_coroutine);
	}

	/**
	 * @param Process $process
	 * @throws Exception
	 */
	public function _load(Process $process)
	{
		Kiri::setProcessId($this->pid);

		putenv('environmental=' . Kiri::PROCESS);

		fire(Event::SERVER_WORKER_START);
		if (Kiri::getPlatform()->isLinux())  {
			name($this->pid, $this->getProcessName());
		}
		if (method_exists($this, 'before')) {
			$this->before($process);
		}
		$this->onHandler($process);
	}


	/**
	 * @return string
	 */
	#[Pure] private function getPrefix(): string
	{
		return static::class;
	}


}
