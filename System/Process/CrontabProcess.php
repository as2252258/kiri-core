<?php


namespace Snowflake\Process;


use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Barrier;
use Swoole\Coroutine\Channel;
use Swoole\Exception;
use Swoole\Timer;

/**
 * Class CrontabProcess
 * @package Snowflake\Process
 */
class CrontabProcess extends Process
{


	public Channel $channel;


	/**
	 * @param \Swoole\Process $process
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		$this->channel = new Channel(5000);

		Coroutine::create([$this, 'execute']);

		Timer::tick(1000, [$this, 'systemLoop']);
	}


	/**
	 * @throws \Exception
	 */
	public function execute()
	{
		$redis = Snowflake::app()->getRedis();
		while (true) {
			$list = $this->channel->pop(-1);
			if (isset($list['isLoop']) && isset($list['tick']) && $list['isLoop'] == 1) {
				$redis->zAdd('system:crontab', time() + $list['tick'], serialize($list));
			}
			try {
				call_user_func($list['handler'], $list['params'] ?? null);
			} catch (\Throwable $throwable) {
				$this->application->addError($throwable->getMessage());
			}
			$redis->release();
		}
	}


	/**
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 * @throws Exception
	 * @throws \Exception
	 */
	public function systemLoop()
	{
		$score = time();
		$redis = Snowflake::app()->getRedis();

		$lists = $redis->zRangeByScore('system:crontab', '0', (string)$score);
		$redis->zRemRangeByScore('system:crontab', '0', (string)$score);

		$barrier = Barrier::make();
		foreach ($lists as $list) {
			$list = unserialize($list);
			if (!isset($_list['handler']) || !is_callable($_list['handler'], true)) {
				continue;
			}
			$this->channel->push($list);
		}
		Barrier::wait($barrier);
		$redis->release();
	}

}
