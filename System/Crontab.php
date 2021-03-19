<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\Component;

/**
 * Class Async
 * @package Snowflake
 */
class Crontab extends Component
{


	public array|Closure $handler;


	public mixed $params;


	public int $tickTime = 1;


	public bool $isLoop = false;


	public int $max_execute_number = -1;


	public int $execute_number = 0;


	/**
	 * @param array|Closure $handler
	 * @param mixed $params
	 * @param int $tickTime
	 * @param bool $isLoop
	 * @param int $max_execute_number
	 * @throws Exception
	 */
	public function dispatch(array|Closure $handler, mixed $params = null, $tickTime = 1, bool $isLoop = false, $max_execute_number = -1)
	{
		$redis = Snowflake::app()->getRedis();

		if (is_array($handler) && is_string($handler[0])) {
			$handler[0] = Snowflake::createObject($handler[0]);
		}

		$executeTime = time() + $tickTime;

		$crontab = new Crontab();
		$crontab->max_execute_number = $max_execute_number;
		$crontab->handler = $handler;
		$crontab->params = $params;
		$crontab->tickTime = $tickTime;
		$crontab->isLoop = $isLoop;

		$redis->zAdd('system:crontab', (string)$executeTime, serialize($crontab));
	}


	/**
	 * @throws Exception
	 */
	public function execute(): void
	{
		$redis = Snowflake::app()->getRedis();
		try {
			$this->execute_number += 1;
			call_user_func($this->handler, $list['params'] ?? null);
			if ($this->isLoop === false) {
				return;
			}
			if ($this->max_execute_number === -1) {
				$redis->zAdd('system:crontab', time() + $this->tickTime, serialize($this));
			} else if ($this->execute_number < $this->max_execute_number) {
				$redis->zAdd('system:crontab', time() + $this->tickTime, serialize($this));
			}
		} catch (\Throwable $throwable) {
			$this->addError($throwable->getMessage());
		} finally {
			$redis->release();
		}
	}


}
