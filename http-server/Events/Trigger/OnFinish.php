<?php


namespace HttpServer\Events\Trigger;


use Exception;
use HttpServer\Events\Callback;
use Swoole\Server;

class OnFinish extends Callback
{
	/**
	 * @param Server $server
	 * @param $task_id
	 * @param $data
	 * @throws Exception
	 */
	public function onHandler(Server $server, $task_id, $data)
	{
		$data = json_decode($data, true);
		$data['work_id'] = $task_id;
		$this->write(var_export($data, true), 'Task');
	}

}
