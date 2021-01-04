<?php


namespace Snowflake;


use Exception;
use HttpServer\IInterface\Task;
use Snowflake\Abstracts\Component;

/**
 * Class Async
 * @package Snowflake
 */
class Async extends Component
{


	/**
	 * @param Task $class
	 * @throws Exception
	 */
	public function dispatch(Task $class)
	{
		$server = Snowflake::app()->server->getServer();
		if (!isset($server->setting['task_worker_num']) || !class_exists($class)) {
			return;
		}

		$randWorkerId = random_int(0, $server->setting['task_worker_num'] - 1);

		$server->task(serialize($class), $randWorkerId);
	}

}
