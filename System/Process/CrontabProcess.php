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
                if (!isset($this->names[$content['name']])) {
                    return;
                }
                $this->names[$content['name']]->clearTimer();
            },
            'clearAll' => function () {
                foreach ($this->names as $name => $crontab) {
                    $crontab->clearTimer();
                }
            },
            default => function () {
                $this->application->error('unknown action');
            }
        }, $content);
    }


    /**
     * @param $content
     */
    private function jobDelivery($content)
    {
        $content = unserialize($content);
        $this->names[$content->getName()] = $content;

        $runTicker = function (Crontab $crontab) {
            $crontab->execute();
        };
        $timer = $content->getTickTime() * 1000;
        if ($content->isLoop()) {
            $timerId = Timer::tick($timer, $runTicker, $content);
        } else {
            $timerId = Timer::after($timer, $runTicker, $content);
        }
        $content->setTimerId($timerId);
    }


}
