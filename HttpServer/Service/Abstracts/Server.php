<?php
declare(strict_types=1);

namespace HttpServer\Service\Abstracts;


use Exception;
use ReflectionException;
use Snowflake\Application;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

trait Server
{

	/** @var Application */
	public $application;


	/**
	 * Server constructor.
	 * @param $host
	 * @param null $port
	 * @param null $mode
	 * @param null $sock_type
	 */
	public function __construct($host, $port = null, $mode = null, $sock_type = null)
	{
		$this->application = Snowflake::app();
		parent::__construct($host, $port, $mode, $sock_type);
	}


	/**
	 * @param array $settings
	 */
	public function set(array $settings)
	{
		parent::set($settings); // TODO: Change the autogenerated stub
		$this->onInit();
	}


	/**
	 * @return void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function onHandlerListener(): void
	{
		$this->on('WorkerStop', $this->createHandler('workerStop'));
		$this->on('WorkerExit', $this->createHandler('workerExit'));
		$this->on('WorkerStart', $this->createHandler('workerStart'));
		$this->on('WorkerError', $this->createHandler('workerError'));
		$this->on('ManagerStart', $this->createHandler('managerStart'));
		$this->on('ManagerStop', $this->createHandler('managerStop'));
		$this->on('PipeMessage', $this->createHandler('pipeMessage'));
		$this->on('Shutdown', $this->createHandler('shutdown'));
		$this->on('Start', $this->createHandler('start'));
		$this->addTask();
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	protected function addTask()
	{
		$settings = $this->setting;
		if (($taskNumber = $settings['task_worker_num'] ?? 0) > 0) {
			$this->on('Finish', $this->createHandler('finish'));
			$this->on('Task', $this->createHandler('task'));
		}
	}


	/**
	 * @param $eventName
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function createHandler($eventName): array
	{
		$classPrefix = 'HttpServer\Events\On' . ucfirst($eventName);
		if (!class_exists($classPrefix)) {
			throw new Exception('class not found.');
		}
		$class = Snowflake::createObject($classPrefix, [Snowflake::app()]);
		return [$class, 'onHandler'];
	}


}
