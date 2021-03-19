<?php


namespace Snowflake;


use Exception;
use HttpServer\IInterface\Task;
use ReflectionException;
use Snowflake\Abstracts\Component;

/**
 * Class Async
 * @package Snowflake
 */
class Async extends Component
{


	/**
	 * @param string $class
	 * @param array $params
	 * @throws Exception
	 * @throws ReflectionException
	 */
	public function dispatch(string $class, array $params)
	{
		$server = Snowflake::app()->getSwoole();
		if (!isset($server->setting['task_worker_num']) || !class_exists($class)) {
			return;
		}

		/** @var Task $class */
		$class = Snowflake::createObject($class);
		$class->setParams($params);

		$randWorkerId = random_int(0, $server->setting['task_worker_num'] - 1);

		$server->task(serialize($class), $randWorkerId);
	}

}
