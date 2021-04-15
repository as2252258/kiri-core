<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;
use Swoole\Timer;
use Snowflake\Cache\Redis;

/**
 * Class Zookeeper
 * @package Snowflake\Process
 */
class Zookeeper extends Process
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
            [$range, $redis] = $this->loadCarobTask();

            $server = Snowflake::app()->getSwoole();
            $setting = $server->setting['worker_num'];
            foreach ($range as $value) {
                $this->dispatch($server, $redis, $setting, $value);
            }
            $redis->release();
        });
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
        $server->sendMessage([
            'action' => 'crontab', 'handler' => swoole_unserialize($redis->get('crontab:' . $value))
        ], random_int(0, $setting - 1));
    }


    /**
     * @return array
     * @throws Exception
     */
    private function loadCarobTask(): array
    {
        $redis = Snowflake::app()->getRedis();

        $startTime = time();

        $range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);
        $redis->zRemRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);

        return [$range, $redis];
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
