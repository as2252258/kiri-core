<?php


namespace Snowflake\Crontab;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\Config;
use Snowflake\Cache\Redis;
use Snowflake\Exception\ConfigException;
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
        $redis = Snowflake::app()->getRedis();
        while (true) {
            $range = $this->loadCarobTask($redis);
            foreach ($range as $value) {
                $this->dispatch($redis, $value);
            }
            Coroutine::sleep(80);
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
            $params['handler'] = $handler;

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
        $range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)time());

        $redis->zRem(Producer::CRONTAB_KEY, ...$range);

        return $range;
    }

}
