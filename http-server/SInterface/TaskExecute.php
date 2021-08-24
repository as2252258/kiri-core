<?php


namespace Server\SInterface;


use Swoole\Server;

interface TaskExecute
{

	public function execute();


	public function finish(Server $server, int $task_id);

}
