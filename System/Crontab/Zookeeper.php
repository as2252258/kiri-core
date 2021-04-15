<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Cache\Redis;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
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
        /** @var \Snowflake\Crontab\Producer $crontab */
        $crontab = Snowflake::app()->get('crontab');
        $crontab->clearAll();
        if (Snowflake::getPlatform()->isLinux()) {
            name($this->pid, 'Crontab zookeeper.');
        }
        $server = Snowflake::app()->getSwoole();
        $setting = $server->setting['worker_num'] + $server->setting['task_worker_num'];
        while (true) {
            [$range, $redis] = $this->loadCarobTask();
            foreach ($range as $value) {
                $this->dispatch($server, $redis, $setting, $value);
            }
            $redis->release();
            sleep(1);
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
                var_dump($handler);
                return;
            }
            $params['handler'] = swoole_unserialize($handler);

            $result = $server->sendMessage($params, $workerId = random_int(0, $setting - 1));

            var_dump('send crontab to ' . $workerId . ' ' . intval($result));
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
        if (!$redis->exists(Producer::CRONTAB_KEY)) {
            var_dump('queue cache not found.');
        } else {
            var_dump('queue cache found.');
        }
        $range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)time());

        $redis->zRem(Producer::CRONTAB_KEY, ...$range);

        return [$range, $redis];
    }

}
