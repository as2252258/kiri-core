<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\BaseObject;

/**
 * Class Async
 * @package Snowflake
 */
class Crontab extends BaseObject
{


	private array|Closure $handler;


	private string $name = '';


	private mixed $params;


	private int $tickTime = 1;


	private bool $isLoop = false;


	private int $max_execute_number = -1;


	private int $execute_number = 0;

	/**
	 * @return array|Closure
	 */
	public function getHandler(): array|Closure
	{
		return $this->handler;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getParams(): mixed
	{
		return $this->params;
	}

	/**
	 * @return int
	 */
	public function getTickTime(): int
	{
		return $this->tickTime;
	}

	/**
	 * @return bool
	 */
	public function isLoop(): bool
	{
		return $this->isLoop;
	}

	/**
	 * @return int
	 */
	public function getMaxExecuteNumber(): int
	{
		return $this->max_execute_number;
	}

	/**
	 * @return int
	 */
	public function getExecuteNumber(): int
	{
		return $this->execute_number;
	}


	/**
	 * @param array|Closure $handler
	 */
	public function setHandler(array|Closure $handler): void
	{
		$this->handler = $handler;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @param mixed $params
	 */
	public function setParams(mixed $params): void
	{
		$this->params = $params;
	}

	/**
	 * @param int $tickTime
	 */
	public function setTickTime(int $tickTime): void
	{
		$this->tickTime = $tickTime;
	}

	/**
	 * @param bool $isLoop
	 */
	public function setIsLoop(bool $isLoop): void
	{
		$this->isLoop = $isLoop;
	}

	/**
	 * @param int $max_execute_number
	 */
	public function setMaxExecuteNumber(int $max_execute_number): void
	{
		$this->max_execute_number = $max_execute_number;
	}

	/**
	 * @param int $execute_number
	 */
	public function setExecuteNumber(int $execute_number): void
	{
		$this->execute_number = $execute_number;
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
