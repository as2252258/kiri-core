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
        $this->channel = new Channel(1000);
        Coroutine\go(function () {
            $this->waitGroup();
        });
        $this->readByWorker($process);
    }


    /**
     * @param $process
     */
    private function readByWorker($process)
    {
        $this->channel->push($process->read());

        Coroutine::sleep(0.01);

        $this->readByWorker($process);
    }


    /**
     * @throws \Exception
     */
    private function waitGroup()
    {
        try {
            $content = $this->channel->pop(-1);

            $_content = json_decode($content, true);
            if (is_null($_content)) {
                $this->jobDelivery($content);
            } else {
                $this->otherAction($_content);
            }
        } catch (\Throwable $exception) {
            $this->application->error($exception->getMessage());
        } finally {
            $this->waitGroup();
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
        $runTicker = [$content->getTickTime() * 1000, function () use ($content) {
            $content->execute($this);
        }];
        if ($content->isLoop()) {
            $this->names[$content->getName()] = Timer::tick(...$runTicker);
        } else {
            $this->names[$content->getName()] = Timer::after(...$runTicker);
        }
    }


}
