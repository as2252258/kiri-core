<?php


namespace Snowflake\Process;


use ReflectionException;
use Snowflake\Crontab;
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
		while (true) {
			/** @var Crontab $list */
			$list = $this->channel->pop(-1);
			$list->execute();
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

		if (empty($lists)) {
			$redis->release();
			return;
		}

		$barrier = Barrier::make();
		foreach ($lists as $list) {
			$list = unserialize($list);
			if (!($list instanceof Crontab)) {
				continue;
			}
			$this->channel->push($list);
		}
		Barrier::wait($barrier);
		$redis->release();
	}

}
