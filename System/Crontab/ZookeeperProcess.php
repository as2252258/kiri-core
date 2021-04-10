<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

/**
 * Class ZookeeperProcess
 * @package Snowflake\Process
 */
class ZookeeperProcess extends Process
{


	private Channel $channel;
	private WaitGroup $waitGroup;


	/** @var Crontab[] $names */
	public array $names = [];


	public array $scores = [];
	public array $timers = [];


	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		$crontab = Snowflake::app()->get('crontab');
		$crontab->clearAll();

		if (Snowflake::getPlatform()->isLinux()) {
			name($this->pid, 'Crontab zookeeper.');
		}
		Timer::tick(1000, function () {
			$startTime = time();

			$redis = Snowflake::app()->getRedis();

			$redis->multi();
			$range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);
			$redis->zRemRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);
            $redis->exec();

            $redis->release();

			/** @var Consumer $consumer */
			$consumer = Snowflake::app()->get(Consumer::class);

			foreach ($range as $value) {
				$consumer->write('crontab:' . md5($value));
			}
		});
	}


	/**
	 * @param string $name
	 */
	public function clear(string $name)
	{
		if (!isset($this->names[$name])) {
			return;
		}
		$timers = $this->timers[$name];

		$search = array_search($name, $this->scores[$timers]);
		if ($search !== false) {
			unset($this->scores[$timers][$search]);
		}
		unset($this->timers[$name], $this->names[$name]);
	}


}
