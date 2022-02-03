<?php

namespace Kiri\Task;

use Kiri\Abstracts\Component;
use Kiri\Core\HashMap;
use Kiri\Exception\ConfigException;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Server\Task;

class CoroutineTaskExecute extends Component
{

	use TaskResolve;


	private HashMap $hashMap;


	private Coroutine\Channel $channel;


	private OnServerTask $taskServer;


	private int $total = 50;


	/**
	 *
	 */
	public function init()
	{
		$this->hashMap = new HashMap();

		$this->channel = new Coroutine\Channel($this->total);

		$this->taskServer = \Kiri::getDi()->get(OnServerTask::class);

		Coroutine::create(function () {
			$barrier = Coroutine\Barrier::make();
			for ($i = 0; $i < 50; $i++) {
				Coroutine::create(function () {
					$this->handler();
				});
			}
			Coroutine\Barrier::wait($barrier);
		});
	}


	/**
	 * @return void
	 * @throws ConfigException
	 */
	protected function handler()
	{
		Coroutine\defer(function () {
			$this->handler();
		});
		$data = $this->channel->pop(-1);

		$task = new Task();
		$task->data = $data;

		$this->taskServer->onCoroutineTask(null, $task);
	}


	/**
	 * @param OnTaskInterface|string $handler
	 * @param array $params
	 * @param int $workerId
	 * @return void
	 * @throws ReflectionException
	 */
	public function execute(OnTaskInterface|string $handler, array $params = [], int $workerId = -1)
	{
		if (is_string($handler)) {
			$handler = $this->handle($handler, $params);
		}
		$this->channel->push(serialize($handler));
	}

}
