<?php

namespace Server\Worker;

use Annotation\Annotation;
use Annotation\Inject;
use Exception;
use ReflectionException;
use Server\Constant;
use Server\Events\OnAfterWorkerStart;
use Server\Events\OnWorkerError;
use Server\Events\OnWorkerExit;
use Server\Events\OnWorkerStart;
use Server\Events\OnWorkerStop;
use Kiri\Abstracts\Config;
use Kiri\Core\Help;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Runtime;
use Kiri\Kiri;
use Swoole\Server;
use Swoole\Timer;


/**
 * Class OnServerWorker
 * @package Server\Worker
 */
class OnServerWorker extends \Server\Abstracts\Server
{


	/**
	 * @var EventDispatch
	 */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerStart(Server $server, int $workerId)
	{
		$this->_setConfigCache($workerId);
		$annotation = Kiri::app()->getAnnotation();
		$annotation->read(APP_PATH . 'app', 'App',
			$workerId < $server->setting['worker_num'] ? [] : [CONTROLLER_PATH]
		);

		$this->eventDispatch->dispatch(new OnWorkerStart($server, $workerId));

		$this->workerInitExecutor($server, $annotation, $workerId);
		$this->interpretDirectory($annotation);

		$this->eventDispatch->dispatch(new OnAfterWorkerStart());
	}


	/**
	 * @param Annotation $annotation
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function interpretDirectory(Annotation $annotation)
	{
		$fileLists = $annotation->runtime(APP_PATH . 'app');
		$di = Kiri::getDi();
		foreach ($fileLists as $class) {
			$instance = $di->get($class);
			foreach ($di->getTargetNote($class) as $value) {
				$value->execute($instance);
			}
			$methods = $di->getMethodAttribute($class);
			foreach ($methods as $method => $attribute) {
				if (empty($attribute)) {
					continue;
				}
				foreach ($attribute as $item) {
					$item->execute($instance, $method);
				}
			}
		}
	}


	/**
	 * @param Server $server
	 * @param Annotation $annotation
	 * @param int $workerId
	 * @throws ConfigException
	 * @throws Exception
	 */
	private function workerInitExecutor(Server $server, Annotation $annotation, int $workerId)
	{
		if ($workerId < $server->setting['worker_num']) {
			$loader = Kiri::app()->getRouter();
			$loader->_loader();

			putenv('environmental=' . Kiri::WORKER);

			echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Worker[%d].%d start.", $server->worker_pid, $workerId) . PHP_EOL;
			$this->setProcessName(sprintf('Worker[%d].%d', $server->worker_pid, $workerId));
		} else {
			putenv('environmental=' . Kiri::TASK);

			echo sprintf("\033[36m[" . date('Y-m-d H:i:s') . "]\033[0m Tasker[%d].%d start.", $server->worker_pid, $workerId) . PHP_EOL;

			$this->setProcessName(sprintf('Tasker[%d].%d', $server->worker_pid, $workerId));
		}
	}


	/**
	 * @param $worker_id
	 * @throws Exception
	 */
	private function _setConfigCache($worker_id)
	{
		putenv('state=start');
		putenv('worker=' . $worker_id);

		$serialize = file_get_contents(storage(Runtime::CONFIG_NAME));
		if (empty($serialize)) {
			return;
		}
		Config::sets(unserialize($serialize));
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerStop(Server $server, int $workerId)
	{
		$this->eventDispatch->dispatch(new OnWorkerStop($server, $workerId));

		Timer::clearAll();
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerExit(Server $server, int $workerId)
	{
		putenv('state=exit');

		$this->eventDispatch->dispatch(new OnWorkerExit($server, $workerId));

		Kiri::getApp('logger')->insert();
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 * @throws Exception
	 */
	public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
		$this->eventDispatch->dispatch(new OnWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal));

		$message = sprintf('Worker#%d::%d error stop. signal %d, exit_code %d, msg %s',
			$worker_id, $worker_pid, $signal, $exit_code, swoole_strerror(swoole_last_error(), 9)
		);
		write($message, 'worker-exit');

		$this->system_mail($message);
	}


	/**
	 * @param $messageContent
	 * @throws Exception
	 */
	protected function system_mail($messageContent)
	{
		try {
			$email = Config::get('email');
			if (!empty($email) && ($email['enable'] ?? false) == true) {
				Help::sendEmail($email, 'Service Error', $messageContent);
			}
		} catch (\Throwable $e) {
			error($e, 'email');
		}
	}

}
