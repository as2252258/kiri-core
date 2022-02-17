<?php

namespace Kiri\Task;

use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Server\SwooleServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


/**
 *
 */
class AsyncTaskExecute extends Component
{


	public TaskManager $hashMap;


	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function init()
	{
		$this->hashMap = $this->getContainer()->get(TaskManager::class);
	}


	/**
	 * @param OnTaskInterface|string $handler
	 * @param array $params
	 * @param int $workerId
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
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
		}
	}


	/**
	 * @param $handler
	 * @param $params
	 * @return object
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @throws Exception
	 */
	private function handle($handler, $params): object
	{
		if (!class_exists($handler) && $this->hashMap->has($handler)) {
			$handler = $this->hashMap->get($handler);
		}
		$implements = $this->getContainer()->getReflect($handler);
		if (!in_array(OnTaskInterface::class, $implements->getInterfaceNames())) {
			throw new Exception('Task must instance ' . OnTaskInterface::class);
		}
		return $implements->newInstanceArgs($params);
	}

}
