<?php


namespace Snowflake\Process;


use Snowflake\Abstracts\Component;
use Snowflake\Application;

/**
 * Class Process
 * @package Snowflake\Snowflake\Service
 */
abstract class Process extends Component
{

	/** @var Application */
	protected $application;


	/**
	 * Process constructor.
	 * @param $application
	 * @param array $config
	 */
	public function __construct(Application $application, $config = [])
	{
		$this->application = $application;
		parent::__construct([]);
	}


	/**
	 * @param \Swoole\Process $process
	 * @return mixed
	 */
	abstract public function onHandler(\Swoole\Process $process);


	/**
	 * @param $process
	 */
	protected function start($process)
	{
		do {
			$this->onHandler($process);
		} while (true);
	}

}
