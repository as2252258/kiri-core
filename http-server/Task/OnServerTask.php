<?php


namespace Server\Task;


use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constrict\Response;
use Server\Constrict\ResponseInterface;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnTaskInterface;
use Swoole\Server;


/**
 * Class OnServerTask
 * @package Server\Task
 */
class OnServerTask
{


	/**
	 * @var ExceptionHandlerInterface|null
	 */
	public ?ExceptionHandlerInterface $handler = null;


	/**
	 * @throws ConfigException
	 */
	public function emit(\Throwable $exception, Response $response): ResponseInterface
	{
		if ($this->handler == null) {
			$exceptionHandler = Config::get('exception.task', ExceptionHandlerDispatcher::class);
			if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
				$exceptionHandler = ExceptionHandlerDispatcher::class;
			}
			$this->handler = Kiri::getDi()->get($exceptionHandler);
		}
		return $this->handler->emit($exception, $response);
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param int $src_worker_id
	 * @param mixed $data
	 * @throws ConfigException
	 */
	public function onTask(Server $server, int $task_id, int $src_worker_id, mixed $data)
	{
		try {
			$data = $this->resolve($data);
		} catch (\Throwable $exception) {
			$data = $this->emit($exception, new Response());
		} finally {
			$server->finish($data);
		}
	}


	/**
	 * @param Server $server
	 * @param Server\Task $task
	 * @throws ConfigException
	 */
	public function onCoroutineTask(Server $server, Server\Task $task)
	{
		try {
			$data = $this->resolve($task->data);
		} catch (\Throwable $exception) {
			$data = $this->emit($exception, new Response());
		} finally {
			$server->finish($data);
		}
	}


	/**
	 * @param $data
	 * @return null
	 * @throws ReflectionException
	 */
	private function resolve($data)
	{
		[$class, $params] = json_encode($data, true);

		$reflect = Kiri::getDi()->getReflect($class);

		if (!$reflect->isInstantiable()) {
			return null;
		}
		$class = $reflect->newInstanceArgs($params);
		return $class->execute();
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param mixed $data
	 */
	public function onFinish(Server $server, int $task_id, mixed $data)
	{
		if (!($data instanceof OnTaskInterface)) {
			return;
		}
		$data->finish($server, $task_id);
	}


}
