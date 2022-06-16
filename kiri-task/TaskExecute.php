<?php

namespace Kiri\Task;

use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Server\ServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Kiri\Di\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Coroutine;
use Swoole\Server;


/**
 *
 */
class TaskExecute extends Component
{


	/**
	 * @param TaskContainer $hashMap
	 * @param ContainerInterface $container
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public TaskContainer $hashMap, public ContainerInterface $container, array $config = [])
	{
		parent::__construct($config);
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
		if ($this->container->has(ServerInterface::class)) {
			$this->onAsyncExec($handler, $workerId);
		} else {
			Coroutine::create(fn() => $this->onCoronExec($handler));
		}
	}


	/**
	 * @param OnTaskInterface|string $handler
	 * @param int $workerId
	 * @return bool
	 * @throws Exception
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function onAsyncExec(OnTaskInterface|string $handler, int $workerId = -1): bool
	{
		/** @var Server $server */
		$server = $this->container->get(ServerInterface::class);
		if ($workerId < 0 || $workerId > $server->setting['task_worker_num']) {
			$workerId = random_int(0, $server->setting['task_worker_num'] - 1);
		}
		return (bool)$server->task(serialize($handler), $workerId);
	}


	/**
	 * @param OnTaskInterface|string $handler
	 * @return bool
	 */
	protected function onCoronExec(OnTaskInterface|string $handler): bool
	{
		$handler->execute();
		$handler->finish(null, Coroutine::getCid());
		return true;
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
		$implements = $this->container->getReflect($handler);
		if (!in_array(OnTaskInterface::class, $implements->getInterfaceNames())) {
			throw new Exception('Task must instance ' . OnTaskInterface::class);
		}
		return $implements->newInstanceArgs($params);
	}

}
