<?php


namespace HttpServer\Events\Trigger\Trigger;


use HttpServer\Events\Callback;
use HttpServer\IInterface\Task as ITask;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Swoole\Timer;
use Exception;

class OnTask extends Callback
{

	/**
	 * @throws Exception
	 */
	public function onHandler()
	{
		$parameter = func_get_args();
		if (count($parameter) < 4) {
			$this->onContinueTask(...func_get_args());
		} else {
			$this->onTask(...func_get_args());
		}
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param int $from_id
	 * @param string $data
	 *
	 * @return mixed|void
	 * @throws Exception
	 * 异步任务
	 */
	public function onTask(Server $server, $task_id, $from_id, $data)
	{
		$time = microtime(TRUE);
		if (empty($data)) {
			return $server->finish('null data');
		}
		$finish = $this->runTaskHandler($data);
		if (!$finish) {
			$finish = [];
		}
		$finish['runTime'] = [
			'startTime' => $time,
			'runTime'   => microtime(TRUE) - $time,
			'endTime'   => microtime(TRUE),
		];
		$server->finish(json_encode($finish));
	}

	/**
	 * @param Server $server
	 * @param Server\Task $task
	 * @return mixed|void
	 * @throws Exception
	 * 异步任务
	 */
	public function onContinueTask(Server $server, Server\Task $task)
	{
		$time = microtime(TRUE);
		if (empty($task->data)) {
			return $task->finish('null data');
		}
		$finish = $this->runTaskHandler($task->data);
		if (!$finish) {
			$finish = [];
		}
		$finish['runTime'] = [
			'startTime' => $time,
			'runTime'   => microtime(TRUE) - $time,
			'endTime'   => microtime(TRUE),
		];
		$task->finish(json_encode($finish));
	}

	/**
	 * @param $data
	 * @return array|null
	 * @throws Exception
	 */
	private function runTaskHandler($data)
	{
		$serialize = $this->before($data);
		try {
			$params = $serialize->getParams();
			if (is_object($params)) {
				$params = get_object_vars($params);
			}
			$finish['class'] = get_class($serialize);
			$finish['params'] = $params;
			$finish['status'] = 'success';
			$finish['info'] = $serialize->handler();
		} catch (\Throwable $exception) {
			$finish['status'] = 'error';
			$finish['info'] = $this->format($exception);
			$this->error($exception, 'Task');
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::RELEASE_ALL);

			$this->endCoroutine();
			Timer::clearAll();
		}
		return $finish;
	}

	/**
	 * @param $data
	 * @return ITask|null
	 */
	protected function before($data)
	{
		if (empty($serialize = unserialize($data))) {
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
	private function format($exception)
	{
		return $exception->getMessage() . " on line " . $exception->getLine() . " at file " . $exception->getFile();
	}

}
