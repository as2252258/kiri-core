<?php


namespace Snowflake\Crontab;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Core\Json;
use Snowflake\Snowflake;
use Swoole\Timer;

/**
 * Class Async
 * @package Snowflake
 */
abstract class Crontab extends BaseObject
{

    const WAIT_END = 'crontab:wait:execute';


    private string $name = '';


    private mixed $params = [];


    private int $tickTime = 1;


    private bool $isLoop = false;


    private int $timerId = -1;


    private int $max_execute_number = -1;


    private int $execute_number = 0;


    /**
     * @return $this
     */
    public function increment(): static
    {
        $this->execute_number += 1;
        return $this;
    }


    /**
     * @return string
     */
    #[Pure] public function getName(): string
    {
        return md5($this->name);
    }

    /**
     * @return mixed
     */
    public function getParams(): mixed
    {
        return $this->params;
    }

    /**
     * @return int
     */
    public function getTickTime(): int
    {
        return $this->tickTime;
    }

    /**
     * @return bool
     */
    public function isLoop(): bool
    {
        return $this->isLoop;
    }

    /**
     * @return int
     */
    public function getMaxExecuteNumber(): int
    {
        return $this->max_execute_number;
    }

    /**
     * @return int
     */
    public function getExecuteNumber(): int
    {
        return $this->execute_number;
    }


    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function setParams(): void
    {
        $this->params = func_get_args();
    }

    /**
     * @param int $tickTime
     */
    public function setTickTime(int $tickTime): void
    {
        $this->tickTime = $tickTime;
    }

    /**
     * @param bool $isLoop
     */
    public function setIsLoop(bool $isLoop): void
    {
        $this->isLoop = $isLoop;
    }

    /**
     * @param int $max_execute_number
     */
    public function setMaxExecuteNumber(int $max_execute_number): void
    {
        $this->max_execute_number = $max_execute_number;
    }

    /**
     * @param int $execute_number
     */
    public function setExecuteNumber(int $execute_number): void
    {
        $this->execute_number = $execute_number;
    }

    /**
     * @return int
     */
    public function getTimerId(): int
    {
        return $this->timerId;
    }

    /**
     * @param int $timerId
     */
    public function setTimerId(int $timerId): void
    {
        $this->timerId = $timerId;
    }


    /**
     *
     * @throws Exception
     */
    public function clearTimer()
    {
        $this->warning('crontab timer clear.');
        if (Timer::exists($this->timerId)) {
            Timer::clear($this->timerId);
        }
    }


    /**
     * @throws Exception
     */
    private function recover(): bool
    {
        $redis = Snowflake::app()->getRedis();
        if ($redis->exists('stop:crontab:' . $this->getName())) {
            $redis->del('crontab:' . $this->getName());
            $redis->del('stop:crontab:' . $this->getName());
        } else {
            $redis->set('crontab:' . ($name = $this->getName()), swoole_serialize($this));
            $tickTime = time() + $this->getTickTime();
            $redis->zAdd(Producer::CRONTAB_KEY, $tickTime, $name);
        }
        return true;
    }


    abstract public function process(): mixed;

    abstract public function max_execute(): mixed;

    abstract public function isStop(): bool;


    /**
     * @throws Exception
     */
    public function execute(): void
    {
        defer(function () {
            $this->isRecover();
        });
        $this->run();
    }


    /**
     * @return bool|int
     * @throws Exception
     */
    public function isRecover(): bool|int
    {
        try {
            $redis = Snowflake::app()->getRedis();
            if ($redis->exists('stop:crontab:' . $this->getName())) {
                $redis->del('stop:crontab:' . $this->getName());
                return true;
            }
            if ($this->isExit()) {
                return $redis->del('crontab:' . $this->getName());
            }
            if ($this->isMaxExecute()) {
                call_user_func([$this, 'max_execute'], ...$this->getParams());
                return $redis->del('crontab:' . $this->getName());
            } else {
                return $this->recover();
            }
        } catch (\Throwable $throwable) {
            return logger()->addError($throwable, 'throwable');
        }
    }


    /**
     * @throws Exception
     */
    private function run()
    {
        try {
            $redis = Snowflake::app()->getRedis();

            $name_md5 = $this->getName();

            $redis->hSet(self::WAIT_END, $name_md5, serialize($this));

            $params = call_user_func([$this, 'process'], ...$this->params);
            $redis->hDel(self::WAIT_END, $name_md5);
            if ($params === null) {
                return;
            }
            $name = date('Y-m-d.log');
            write(storage($name, '/log/crontab'), Json::encode([
                'name'     => $this->name,
                'response' => serialize($params)
            ]));
        } catch (\Throwable $throwable) {
            logger()->addError($throwable, 'throwable');
        }
    }


    /**
     * @return bool
     */
    private function isExit(): bool
    {
        if ($this->isStop()) {
            return true;
        }
        if (!$this->isLoop) {
            return true;
        }
        return false;
    }


    /**
     * @return bool
     */
    private function isMaxExecute(): bool
    {
        if ($this->max_execute_number !== -1) {
            return $this->execute_number >= $this->max_execute_number;
        }
        return false;
    }


}
