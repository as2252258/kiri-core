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


    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void
    {
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
        Timer::exists($this->names[$name]) && Timer::clear($this->names[$name]);
        unset($this->names[$name]);
    }


    /**
     * @param $content
     */
    private function jobDelivery($content)
    {
        /** @var Crontab $content */
        $content = unserialize($content);

        $name = $content->getName();
        if (isset($this->names[$name])) {
            Timer::clear($this->names[$name]);
        }
        $callback = function () use ($content) {
            var_dump('executes', Timer::stats());
//            $content->execute($this);
        };
        $runTicker = [$content->getTickTime() * 1000, $callback];
        if ($content->isLoop()) {
            $this->names[$name] = Timer::tick(...$runTicker);
        } else {
            $this->names[$name] = Timer::after(...$runTicker);
        }
        var_dump($this->names);
    }


}
