<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Abstracts\Config;
use Snowflake\Cache\Redis;
use Snowflake\Exception\ConfigException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Timer;
use Throwable;

/**
 * Class Zookeeper
 * @package Snowflake\Process
 */
class Zookeeper extends Process
{


	private int $workerNum = 0;


	private mixed $server;


	/**
	 * @return string
	 * @throws ConfigException
	 */
	public function getProcessName(): string
	{
		$name = Config::get('id', 'system') . '[' . $this->pid . ']';
		if (!empty($prefix)) {
			$name .= '.Crontab zookeeper';
		}
		return $name;
	}


	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function before(\Swoole\Process $process): void
	{
		/** @var Producer $crontab */
		$crontab = Snowflake::app()->get('crontab');
		$crontab->clearAll();

		$this->server = $server = Snowflake::app()->getSwoole();
		$this->workerNum = $server->setting['worker_num'] + $server->setting['task_worker_num'];
	}


	/**
	 * @param \Swoole\Process $process
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Process $process): void
	{
		Timer::tick(100, [$this, 'loop']);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function loop()
	{
		$redis = Snowflake::app()->getRedis();
		defer(fn() => $redis->release());
		$range = $this->loadCarobTask($redis);
		foreach ($range as $value) {
			$this->dispatch($redis, $value);
		}
	}


	/**
	 * @param Redis|\Redis $redis
	 * @param $value
	 * @throws Exception
	 */
	private function dispatch(Redis|\Redis $redis, $value)
	{
		try {
			$params['action'] = 'crontab';
			if (empty($handler = $redis->get('crontab:' . $value))) {
				return;
			}
			$params['handler'] = swoole_unserialize($handler);

			$this->server->sendMessage($params, $this->getWorker());
		} catch (Throwable $exception) {
			logger()->addError($exception);
		}
	}


	/**
	 * @return int
	 * @throws \Exception
	 */
	private function getWorker(): int
	{
		return random_int(0, $this->workerNum - 1);
	}


	/**
	 * @param Redis|\Redis $redis
	 * @return array
	 */
	private function loadCarobTask(Redis|\Redis $redis): array
	{
		$script = <<<SCRIPT
local _two = redis.call('ZRANGEBYSCORE', KEYS[1], '0', ARGV[1])

redis.call('ZREM', KEYS[1], unpack(_two))

return _two
SCRIPT;
		return $redis->eval($script, [Producer::CRONTAB_KEY, (string)time()], 1);
	}

}
