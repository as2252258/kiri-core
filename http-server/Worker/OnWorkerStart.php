<?php

namespace Server\Worker;

use Annotation\Annotation;
use Annotation\Inject;
use Exception;
use Http\Route\Router;
use Kiri\Abstracts\Config;
use Kiri\Di\NoteManager;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Server\ServerManager;

class OnWorkerStart implements EventDispatcherInterface
{


	/**
	 * @var Annotation
	 */
	#[Inject(Annotation::class)]
	public Annotation $annotation;


	/**
	 * @var Router
	 */
	#[Inject(Router::class)]
	public Router $router;


	/**
	 * @param object $event
	 * @return void
	 * @throws ConfigException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function dispatch(object $event)
	{
		$isWorker = $event->workerId < $event->server->setting['worker_num'];

		$time = microtime(true);

		$this->interpretDirectory($isWorker);
		if ($isWorker) {
			ServerManager::setEnv('environmental', Kiri::WORKER);
			Kiri::getFactory()->getRouter()->_loader();

			$this->setProcessName(sprintf('Worker[%d].%d', $event->server->worker_pid, $event->workerId));
		} else {
			ServerManager::setEnv('environmental', Kiri::TASK);

			$this->setProcessName(sprintf('Tasker[%d].%d', $event->server->worker_pid, $event->workerId));
		}
		echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Builder %s[%d].%d use time %s.", $isWorker ? 'Worker' : 'Taker',
				$event->server->worker_pid, $event->workerId, round(microtime(true) - $time, 6) . 's') . PHP_EOL;
	}


	/**
	 * @param $prefix
	 * @throws ConfigException
	 */
	protected function setProcessName($prefix)
	{
		if (Kiri::getPlatform()->isMac()) {
			return;
		}
		$name = Config::get('id', 'system-service');
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
		swoole_set_process_name($name);
	}


	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function interpretDirectory($isWorker)
	{
		$di = Kiri::getDi();

		$namespace = array_filter(explode('\\', Router::getNamespace()));

		$namespace = APP_PATH . implode('/', $namespace);

		$this->annotation->read(APP_PATH . 'app', 'App', $isWorker ? [] : [$namespace]);

		$fileLists = $this->annotation->read(APP_PATH . 'app');
		foreach ($fileLists->runtime(APP_PATH . 'app') as $class) {
			foreach (NoteManager::getTargetNote($class) as $value) {
				$value->execute($class);
			}
			$methods = $di->getMethodAttribute($class);
			foreach ($methods as $method => $attribute) {
				if (empty($attribute)) {
					continue;
				}
				foreach ($attribute as $item) {
					$item->execute($class, $method);
				}
			}
		}
	}

}
