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

    /** @var Crontab[] $names */
    public array $names = [];


    /**
     * @param \Swoole\Process $process
     */
    public function onHandler(\Swoole\Process $process): void
    {
        while (true) {
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
            }
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
        $runTicker = function (Crontab $crontab) {
            $this->application->warning('execute crontab '.date('Y-m-d H:i:s'));

            if (!file_exists(APP_PATH . 'runTicker.log')) {
                touch(APP_PATH . 'runTicker.log');
            }

            file_put_contents(APP_PATH . 'runTicker.log', date('Y-m-d H:i:s'), FILE_APPEND);

            $crontab->execute($this);
        };
        $timer = $content->getTickTime() * 10;
        if ($content->isLoop()) {

            $this->application->warning('loop crontab');

            $content->setTimerId(Timer::tick($timer, $runTicker, $content));
        } else {
            $this->application->warning('after crontab');

            $content->setTimerId(Timer::after($timer, $runTicker, $content));
        }
        $this->names[$content->getName()] = $content;
    }


}
