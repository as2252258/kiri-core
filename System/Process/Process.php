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
	 * @throws \Exception
	 */
	public function __construct($application, $name)
	{
		parent::__construct(function (\Swoole\Process $process) use ($name) {
			if (Snowflake::isLinux()) {
				$this->name($name);
			}
			$this->onHandler($process);
		}, false, 1, true);
		$this->application = $application;
		Snowflake::setWorkerId($this->pid);
	}

}
