<?php

use SInterface\TaskExecute;
use Swoole\Server;


/**
 * Class RegisterTask
 */
class RegisterTask implements TaskExecute
{


	/**
	 * RegisterTask constructor.
	 * @param mixed $data
	 */
	public function __construct(public mixed $data)
	{

	}


	/**
	 *
	 */
	public function execute()
	{
		// TODO: Implement execute() method.
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 */
	public function finish(Server $server, int $task_id)
	{
		// TODO: Implement finish() method.
	}
}
