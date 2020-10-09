<?php


namespace Snowflake\Process;


use Snowflake\Abstracts\Component;
use Snowflake\Application;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine\Socket;
use Swoole\Process\Pool;

/**
 * Class Process
 * @package Snowflake\Snowflake\Service
 */
abstract class Process extends \Swoole\Process
{

	/** @var Application $application */
	protected $application;


	/**
	 * Process constructor.
	 * @param $application
	 * @param $name
	 * @param bool $enable_coroutine
	 * @throws \Exception
	 */
	public function __construct($application, $name, $enable_coroutine = true)
	{
		$class = get_called_class();
		parent::__construct(function ($process) use ($name, $class) {
			if (Snowflake::isLinux()) {
				$this->name('Processes: ' . $class . '::class');
			}
			$this->onHandler($process);
		}, false, 1, $enable_coroutine);
		$this->application = $application;
		Snowflake::setWorkerId($this->pid);
	}

}
