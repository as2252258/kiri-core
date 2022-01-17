<?php

namespace Kiri\Task;

use Kiri\Abstracts\Component;
use Kiri\Core\HashMap;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Server;

class TaskManager extends Component
{


	private HashMap $hashMap;


	/**
	 *
	 */
	public function init()
	{
		$this->hashMap = new HashMap();
	}


	/**
	 * @param Server $swollen
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function taskListener(Server $swollen)
	{
		if (!isset($swollen->setting['task_worker_num']) || $swollen->setting['task_worker_num'] < 1) {
			return;
		}

		$task_use_object = $swollen->setting['task_object'] ?? $swollen->setting['task_use_object'] ?? false;
		$reflect = $this->container->get(OnServerTask::class);

		$swollen->on('finish', [$reflect, 'onFinish']);
		if ($task_use_object || $swollen->setting['task_enable_coroutine']) {
			$swollen->on('task', [$reflect, 'onCoroutineTask']);
		} else {
			$swollen->on('task', [$reflect, 'onTask']);
		}
	}


	/**
	 * @param string $key
	 * @param $handler
	 */
	public function add(string $key, $handler)
	{
		$this->hashMap->put($key, $handler);
	}


	/**
	 * @param string $key
	 * @return OnTaskInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function get(string $key): OnTaskInterface
	{
		$task = $this->hashMap->get($key);
		if (is_string($task)) {
			$task = $this->getContainer()->get($task);
		}
		return $task;
	}


}
