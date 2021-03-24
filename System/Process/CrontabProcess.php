<?php


namespace Snowflake\Process;


use Exception;
use ReflectionException;
use Snowflake\Crontab;
use Snowflake\Abstracts\Crontab as ACrontab;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

/**
 * Class CrontabProcess
 * @package Snowflake\Process
 */
class CrontabProcess extends Process
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

        Timer::tick(1000, function () {
            $startTime = time();

            $redis = Snowflake::app()->getRedis();

            $range = $redis->zRangeByScore(ACrontab::CRONTAB_KEY, '0', (string)$startTime);
            $redis->zRemRangeByScore(ACrontab::CRONTAB_KEY, '0', (string)$startTime);
            foreach ($range as $value) {
                $crontab = $redis->get('crontab:' . md5($value));
                $redis->del('crontab:' . md5($value));
                if (empty($crontab) || !($crontab = swoole_unserialize($crontab))) {
                    continue;
                }
                Coroutine::create(function (Crontab $value, int $startTime) {
                    $this->dispatch($value);
                }, $crontab, $startTime);
            }
            $redis->release();
        });
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

        $redis->zAdd(ACrontab::CRONTAB_KEY, $tickTime, $crontab->getName());
    }


}
