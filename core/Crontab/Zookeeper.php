<?php


namespace Kiri\Crontab;


use Exception;
use Kiri\Abstracts\Config;
use Kiri\Cache\Redis;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Abstracts\CustomProcess;
use Server\ServerManager;
use Swoole\Process;
use Swoole\Timer;
use Throwable;

/**
 * Class Zookeeper
 * @package Kiri\Process
 */
class Zookeeper extends CustomProcess
{


	private int $workerNum = 0;


	private int $_timer = -1;


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
		$this->_timer = Timer::tick(300, [$this, 'loop']);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function loop()
	{
		if ($this->checkProcessIsStop()) {
			$this->exit();
			Timer::clear($this->_timer);
			return;
		}
		$redis = Kiri::app()->getRedis();
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
			if (empty($handler = $redis->get('crontab:' . $value))) {
				return;
			}
			$server = di(ServerManager::class)->getServer();
			$server->sendMessage(swoole_unserialize($handler), $this->getWorker());
		} catch (Throwable $exception) {
			logger()->addError($exception);
		}
	}


	/**
	 * @return int
	 * @throws Exception
	 */
	private function getWorker(): int
	{
		if ($this->workerNum == 0) {
			$server = di(ServerManager::class)->getServer();

			$this->workerNum = $server->setting['worker_num'] + ($server->setting['task_worker_num'] ?? 0);
		}
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
