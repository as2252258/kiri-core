<?php

namespace Kiri\Server;

use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Task\OnServerTask;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Server\Task;

class CoroutineTasker extends Component
{


	public Channel $channel;


	/**
	 * @return void
	 * @throws ConfigException
	 */
	public function initCore()
	{
		$taskWorker = Config::get('server.settings.task_worker_num', 0);

		if ($taskWorker > 1) {
			$this->channel = new Channel($taskWorker);
			for ($i = 0; $i < $taskWorker; $i++) {

				Coroutine::create(function () {
					while ($this->channel->capacity) {
						$data = $this->channel->pop(-1);

						$execute = $this->getContainer()->get(OnServerTask::class);

						$task = new Task();
						$task->data = $data;
						$execute->onCoroutineTask(null, $task);
					}
				});
			}
		}
	}


	public function dispatch($data)
	{
		$this->channel->push($data);
	}


}
