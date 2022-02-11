<?php

namespace Kiri\Task;

use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Server\SwooleServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Process;


/**
 *
 */
class AsyncTaskExecute extends Component
{


	use TaskResolve;


	private int $total = 50;

	/**
	 * @param int $total
	 */
	public function setTotal(int $total): void
	{
		$this->total = $total;
	}


	/**
	 * @return void
	 * @throws Kiri\Exception\ConfigException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function start()
	{
		$processManager = $this->getContainer()->get(Kiri\Server\ProcessManager::class);
		for ($i = 0; $i < $this->total; $i++) {
			$class = new TaskProcess();
			$class->name = 'task.' . $i;

			$processManager->add($class, 'tasker');
		}
	}


	/**
	 * @param OnTaskInterface|string $handler
	 * @param array $params
	 * @param int $workerId
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @throws Exception
	 */
	public function execute(OnTaskInterface|string $handler, array $params = [], int $workerId = -1)
	{
		if (is_string($handler)) {
			$handler = $this->handle($handler, $params);
		}
		$container = $this->getContainer();
		if ($container->has(SwooleServerInterface::class)) {
			$server = $container->get(SwooleServerInterface::class);
			if ($workerId < 0 || $workerId > $server->setting['task_worker_num']) {
				$workerId = random_int(0, $server->setting['task_worker_num'] - 1);
			}
			$server->task(serialize($handler), $workerId);
		} else {
			if ($workerId < 0 || $workerId > $this->total) {
				$workerId = random_int(0, $this->total - 1);
			}

			$processManager = $container->get(Kiri\Server\ProcessManager::class);

			/** @var Process $process */
			$process = $processManager->get('task.' . $workerId, 'tasker');
			$process->write(serialize($handler));
		}
	}


}
