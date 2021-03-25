<?php


namespace Snowflake\Crontab;


use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

/**
 * Class CrontabZookeeperProcess
 * @package Snowflake\Process
 */
class CrontabZookeeperProcess extends Process
{


    private Channel $channel;
    private WaitGroup $waitGroup;


    /** @var Crontab[] $names */
    public array $names = [];


    public array $scores = [];
    public array $timers = [];


    /**
     * @param \Swoole\Process $process
     * @throws ReflectionException
     * @throws ComponentException
     * @throws NotFindClassException
     */
    public function onHandler(\Swoole\Process $process): void
    {
        $crontab = Snowflake::app()->get('crontab');
        $crontab->clearAll();

        $process->name('Crontab zookeeper.');
        Timer::tick(1000, function () {
            $startTime = time();

            $redis = Snowflake::app()->getRedis();

            $range = $redis->zRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);
            $redis->zRemRangeByScore(Producer::CRONTAB_KEY, '0', (string)$startTime);

            /** @var Consumer $consumer */
            $consumer = Snowflake::app()->get(Consumer::class);

            foreach ($range as $value) {
                $consumer->write('crontab:' . md5($value));
            }
            $redis->release();
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
