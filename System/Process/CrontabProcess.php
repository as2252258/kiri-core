<?php


namespace Snowflake\Process;


use Snowflake\Crontab;
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
     */
    public function onHandler(\Swoole\Process $process): void
    {
        $this->readBySocket($process);
        Timer::tick(1000, function () {
            $startTime = time();

            var_dump($startTime);

            $time = $this->scores[$startTime];
            unset($this->scores[$startTime]);
            foreach ($time as $value) {
                Coroutine::create(function (Crontab $value, int $startTime) {
                    $this->dispatch($value, $startTime);
                }, $value, $startTime);
            }
        });
    }


    /**
     * @param Crontab $value
     * @param int $startTime
     * @throws \Exception
     */
    private function dispatch(Crontab $value, int $startTime)
    {
        try {
            $value->increment()->execute();
            if ($value->getExecuteNumber() < $value->getMaxExecuteNumber()) {
                $this->addTask($value, $startTime + $value->getTickTime());
            } else if ($value->isLoop()) {
                $this->addTask($value, $startTime + $value->getTickTime());
            }
        } catch (\Throwable $exception) {
            $this->application->error($exception->getMessage());
        }
    }


    /**
     * @param \Swoole\Process $process
     * @throws \Exception
     */
    public function readBySocket(\Swoole\Process $process)
    {
        Coroutine::create(function (\Swoole\Process $process) {
            try {
                $content = $process->read();

                $_content = json_decode($content, true);
                if (is_null($_content)) {
                    $this->jobDelivery($content);
                } else {
                    $this->otherAction($_content);
                }
            } catch (\Throwable $exception) {
                $this->application->error($exception->getMessage());
            } finally {
                $this->onHandler($process);
            }
        }, $process);
    }


    /**
     * @param $content
     */
    private function otherAction($content)
    {
        call_user_func(match ($content['action']) {
            'clear' => function ($content) {
                $this->clear($content['name']);
            },
            'clearAll' => function () {
                $this->names = [];
                Timer::clearAll();
            },
            default => function () {
                $this->application->error('unknown action');
            }
        }, $content);
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
     * @param $content
     */
    private function jobDelivery($content)
    {
        /** @var Crontab $content */
        $content = unserialize($content);

        $ticker = intval($content->getTickTime() * 1000) + time();

        $this->addTask($content, $ticker);
    }


    /**
     * @param Crontab $content
     * @param $ticker
     */
    private function addTask(Crontab $content, $ticker)
    {
        $name = $content->getName();
        if (isset($this->names[$name])) {
            unset($this->names[$content->getName()]);

            $search = array_search($content->getName(), $this->scores);
            unset($this->scores[$search]);
        }

        if (!isset($this->scores[$ticker])) {
            $this->scores[$ticker] = [];
        }

        $this->timers[$content->getName()] = $ticker;
        $this->scores[$ticker][] = $content->getName();
        $this->names[$content->getName()] = $content;

        ksort($this->scores, SORT_NUMERIC);
    }


}
