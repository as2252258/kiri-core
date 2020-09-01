<?php


namespace Snowflake\Process;


use Snowflake\Abstracts\Component;
use Snowflake\Application;
use Snowflake\Exception\ComponentException;
use Swoole\Coroutine\Socket;
use Swoole\Process\Pool;

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
	 * @param $workerId
	 * @return Socket
	 * @throws ComponentException
	 */
	protected function exportSocket($workerId)
	{
		return $this->application->get(Pool::class)->getProcess($workerId)->exportSocket();
	}


	/**
	 */
	protected function start()
	{
		$_process = new \Swoole\Process([$this, 'onHandler'], false, null, true);
		$_process->start();
	}

}
