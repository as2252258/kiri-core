<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Event;
use Snowflake\Process\Process;
use Snowflake\Snowflake;
use Swoole\Coroutine;


/**
 * Class Consumer
 * @package Snowflake\Crontab
 */
class Consumer extends Process
{

    public Coroutine\Channel $channel;


    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void
    {
        $process->name('Crontab consumer');

        $this->channel = new Coroutine\Channel(2000);
        go(function () {
            $this->popChannel();
        });
        $this->tick($process);
    }


    /**
     * @throws Exception
     */
    public function popChannel()
    {
        $crontab = $this->channel->pop(-1);

        go(function () use ($crontab) {
            $this->dispatch($crontab);
        });

        $this->popChannel();
    }


    /**
     * @param \Swoole\Process $process
     * @throws \ReflectionException
     * @throws \Snowflake\Exception\ComponentException
     * @throws \Snowflake\Exception\ConfigException
     * @throws \Snowflake\Exception\NotFindClassException
     */
    public function tick(\Swoole\Process $process)
    {
        [$value, $startTime] = swoole_unserialize($process->read());

        $redis = Snowflake::app()->getRedis();

        $crontab = swoole_unserialize($redis->get($value));
        $redis->del($value);
        if (is_object($crontab)) {
            $this->channel->push($crontab);
        }

        $redis->release();

        $this->tick($process);
    }


    /**
     * @param Crontab $value
     * @throws Exception
     */
    private function dispatch(Crontab $value)
    {
        $value->increment()->execute();
        if ($value->getExecuteNumber() < $value->getMaxExecuteNumber()) {
            $this->addTask($value);
        } else if ($value->isLoop()) {
            $this->addTask($value);
        }
        var_dump($value);
        fire(Event::SYSTEM_RESOURCE_RELEASES);
    }


    /**
     * @param Crontab $crontab
     * @throws Exception
     */
    private function addTask(Crontab $crontab)
    {
        $redis = Snowflake::app()->getRedis();

        $name = md5($crontab->getName());

        $redis->set('crontab:' . $name, swoole_serialize($crontab));

        $tickTime = time() + $crontab->getTickTime();

        $redis->zAdd(Producer::CRONTAB_KEY, $tickTime, $crontab->getName());
    }


}
