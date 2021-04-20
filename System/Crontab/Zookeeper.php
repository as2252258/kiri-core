<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Abstracts\Config;
use Snowflake\Cache\Redis;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Throwable;

/**
 * Class Zookeeper
 * @package Snowflake\Process
 */
class Zookeeper extends Process
{

	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		/** @var Producer $crontab */
		$crontab = Snowflake::app()->get('crontab');
		$crontab->clearAll();
		if (Snowflake::getPlatform()->isLinux()) {
			name($this->pid, 'Crontab zookeeper.');
		}

		$ticker = Config::get('crontab.ticker', 100) / 1000;

		$server = Snowflake::app()->getSwoole();
		$setting = $server->setting['worker_num'] + $server->setting['task_worker_num'];
		while (true) {
			[$range, $redis] = $this->loadCarobTask();
			foreach ($range as $value) {
				$this->dispatch($server, $redis, $setting, $value);
			}
			$redis->release();

			Coroutine::sleep($ticker);
		}
	}


	/**
	 * @param $server
	 * @param Redis|\Redis $redis
	 * @param int $setting
	 * @param $value
	 * @throws Exception
	 */
	private function dispatch($server, Redis|\Redis $redis, int $setting, $value)
	{
		try {
			$params['action'] = 'crontab';
			if (empty($handler = $redis->get('crontab:' . $value))) {
				return;
			}
			$params['handler'] = swoole_unserialize($handler);

			$server->sendMessage($params, random_int(0, $setting - 1));
		} catch (Throwable $exception) {
			logger()->addError($exception);
		}

	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function loadCarobTask(): array
	{
		$redis = Snowflake::app()->getRedis();

		$range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)time());

		$redis->zRem(Producer::CRONTAB_KEY, ...$range);

		return [$range, $redis];
	}

}
