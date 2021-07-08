<?php
declare(strict_types=1);


namespace Snowflake\Process;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Event;
use Snowflake\Snowflake;

/**
 * Class Process
 * @package Snowflake\Snowflake\Service
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
		Snowflake::setProcessId($this->pid);

		putenv('environmental=' . Snowflake::PROCESS);

		fire(Event::SERVER_WORKER_START);
		if (Snowflake::getPlatform()->isLinux())  {
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
