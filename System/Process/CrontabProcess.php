<?php


namespace Snowflake\Process;


use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Crontab;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Barrier;
use Swoole\Coroutine\Channel;
use Swoole\Exception;
use Swoole\Timer;

/**
 * Class CrontabProcess
 * @package Snowflake\Process
 */
class CrontabProcess extends Process
{


    private Channel $channel;


    /** @var Crontab[] $names */
    public array $names = [];


    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void
    {
        $this->channel = new Channel(1000);
        Coroutine::create(function () {
            $this->readByCha();
        });
        while (true) {
            $this->channel->push($process->read());
        }
    }


    private function readByCha()
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
                foreach ($this->names as $name => $crontab) {
                    $crontab->clearTimer();

                    unset($this->names[$name], $crontab);
                }
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
        $this->names[$name]->clearTimer();
    }


    /**
     * @param $content
     */
    private function jobDelivery($content)
    {
        /** @var Crontab $content */
        $content = unserialize($content);
        $runTicker = function () use ($content) {
            $this->application->warning('execute crontab ' . date('Y-m-d H:i:s'));
            $content->execute($this);
        };
//        $timer = $content->getTickTime() * 10;

        Timer::after(10000, function () use ($content) {
            $this->application->warning('execute crontab ' . date('Y-m-d H:i:s'));
            $content->execute($this);
        });

//        if ($content->isLoop()) {
//            $content->setTimerId(Timer::tick($timer, function () use ($content) {
//                $this->application->warning('execute crontab ' . date('Y-m-d H:i:s'));
//                $content->execute($this);
//            }));
//        } else {
//            $content->setTimerId(Timer::after($timer, function () use ($content) {
//                $this->application->warning('execute crontab ' . date('Y-m-d H:i:s'));
//                $content->execute($this);
//            }));
//        }
//        $this->names[$content->getName()] = $content;
    }


}
