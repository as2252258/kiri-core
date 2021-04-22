<?php

declare(strict_types=1);

namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use HttpServer\IInterface\Task;
use HttpServer\IInterface\Task as ITask;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Server;
use Swoole\Timer;
use Exception;

/**
 * Class OnTask
 * @package HttpServer\Events
 */
class OnTask extends Callback
{

	/**
	 * @throws Exception
	 */
	public function onHandler()
	{
		$setting = Snowflake::app()->getSwoole();

		$isCoroutineTask = $setting->setting['task_enable_coroutine'] ?? false;
		if ($isCoroutineTask === true) {
			call_user_func([$this, 'onContinueTask'], ...func_get_args());
		} else {
			call_user_func([$this, 'onTask'], ...func_get_args());
		}
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param int $from_id
	 * @param string $data
	 *
	 * @return mixed
	 * @throws Exception 异步任务
	 */
	public function onTask(Server $server, int $task_id, int $from_id, string $data): mixed
	{
		if (empty($data)) {
			return $server->finish('null data');
		}

		$time = microtime(TRUE);
		$finish = $this->runTaskHandler($data);
		if (!$finish) {
			$finish = [];
		}
		$finish['runTime'] = [
			'startTime' => $time,
			'runTime'   => microtime(TRUE) - $time,
			'endTime'   => microtime(TRUE),
		];
		return $server->finish(json_encode($finish));
	}

	/**
	 * @param Server $server
	 * @param Server\Task $task
	 * @return mixed
	 * @throws Exception 异步任务
	 */
	public function onContinueTask(Server $server, Server\Task $task): mixed
	{
		if (empty($task->data)) {
			return $task->finish('null data');
		}

		$time = microtime(TRUE);
		$finish = $this->runTaskHandler($task->data);
		if (!$finish) {
			$finish = [];
		}
		$finish['runTime'] = [
			'startTime' => $time,
			'runTime'   => microtime(TRUE) - $time,
			'endTime'   => microtime(TRUE),
		];
		return $task->finish(json_encode($finish));
	}


	/**
	 * @param $data
	 * @return array|null
	 * @throws Exception
	 */
	private function runTaskHandler($data): ?array
	{
		try {
            defer(function () {
                fire(Event::SYSTEM_RESOURCE_CLEAN);
                logger_insert();
            });
            $serialize = $this->before($data);
			if ($serialize === null) {
				throw new Exception('unpack error.');
			}
			$params = $serialize->getParams();
			if (is_object($params)) {
				$params = get_object_vars($params);
			}
			$finish['class'] = get_class($serialize);
			$finish['params'] = $params;
			$finish['status'] = 'success';
			$finish['info'] = $serialize->onHandler();
			return $finish;
		} catch (\Throwable $exception) {
			$finish['status'] = 'error';
			$finish['info'] = $this->format($exception);
			$this->addError($exception, 'Task');

			return $finish;
		}
	}


	/**
	 * @param $data
	 * @return ITask|null
	 */
	protected function before($data): ?Task
	{
		if (empty($serialize = swoole_unserialize($data))) {
			return null;
		}
		if (!($serialize instanceof ITask)) {
			return null;
		}
		return $serialize;
	}

	/**
	 * @param $exception
	 * @return string
	 */
	private function format($exception): string
	{
		return $exception->getMessage() . " on line " . $exception->getLine() . " at file " . $exception->getFile();
	}

}
