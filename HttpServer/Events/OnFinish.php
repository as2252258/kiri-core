<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnFinish
 * @package HttpServer\Events
 */
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
