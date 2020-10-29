<?php
declare(strict_types=1);


namespace Snowflake\Process;


use Exception;
use Snowflake\Application;
use Snowflake\Snowflake;

/**
 * Class Process
 * @package Snowflake\Snowflake\Service
 */
abstract class Process extends \Swoole\Process
{

	/** @var Application $application */
	protected Application $application;


	/**
	 * Process constructor.
	 * @param $application
	 * @param $name
	 * @param bool $enable_coroutine
	 * @throws Exception
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
