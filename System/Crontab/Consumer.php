<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Event;
use Snowflake\Process\Process;
use Snowflake\Snowflake;


/**
 * Class Consumer
 * @package Snowflake\Crontab
 */
class Consumer extends Process
{


    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void
    {
        // TODO: Implement onHandler() method.
        $redis = Snowflake::app()->getRedis();

        $process->name('Crontab consumer');

        while (true) {
            [$value, $startTime] = swoole_unserialize($process->read());

            $crontab = swoole_unserialize($redis->get($value));
            $redis->del($value);
            if (!is_object($crontab)) {
                continue;
            }
            $this->dispatch($crontab);
        }
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
