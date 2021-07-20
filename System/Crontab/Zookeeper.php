<?php


namespace Snowflake\Crontab;


use Exception;
use Server\SInterface\CustomProcess;
use Snowflake\Abstracts\Config;
use Snowflake\Cache\Redis;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Process;
use Swoole\Timer;
use Throwable;

/**
 * Class Zookeeper
 * @package Snowflake\Process
 */
class Zookeeper implements CustomProcess
{


	private int $workerNum = 0;


	private mixed $server;


	/**
	 * @param Process $process
	 * @return string
	 * @throws ConfigException
	 */
	public function getProcessName(Process $process): string
	{
		$name = Config::get('id', 'system') . '[' . $process->pid . ']';
		if (!empty($prefix)) {
			$name .= '.Crontab zookeeper';
		}
		return $name;
	}


	/**
	 * @param Process $process
	 * @throws Exception
	 */
	public function onHandler(Process $process): void
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
local _two = redis.call('zRangeByScore', KEYS[1], '0', ARGV[1])

if (table.getn(_two) > 0) then
	redis.call('ZREM', KEYS[1], unpack(_two))
end

return _two
SCRIPT;
		return $redis->eval($script, [Producer::CRONTAB_KEY, (string)time()], 1);
	}

}
