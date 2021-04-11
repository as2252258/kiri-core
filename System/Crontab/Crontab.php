<?php


namespace Snowflake\Crontab;


use Closure;
use Exception;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Timer;

/**
 * Class Async
 * @package Snowflake
 */
class Crontab extends BaseObject
{

    const WAIT_END = 'crontab:wait:execute';

    private array|Closure $handler;


    private string $name = '';


    private mixed $params = null;


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
     * @return array|Closure
     */
    public function getHandler(): array|Closure
    {
        return $this->handler;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
     * @param array|Closure $handler
     */
    public function setHandler(array|Closure $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param mixed $params
     */
    public function setParams(mixed $params): void
    {
        $this->params = $params;
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
     * @throws \Exception
     */
    private function recover()
    {
        $redis = Snowflake::app()->getRedis();

        $redis->set('crontab:' . md5($this->getName()), swoole_serialize($this));

        $tickTime = time() + $this->getTickTime();

        $redis->zAdd(Producer::CRONTAB_KEY, $tickTime, $this->getName());
    }


    /**
     * @throws Exception
     */
    public function execute(): void
    {
        try {
            $redis = Snowflake::app()->getRedis();

            $name_md5 = md5($this->getName());

            $redis->hSet(self::WAIT_END, $name_md5, serialize($this));
            $params = call_user_func($this->handler, $this->params, $this->name);
            $redis->hDel(self::WAIT_END, $name_md5);
            if ($params === null) {
                return;
            }
            $name = date('Y_m_d_H_i_s.' . $this->name . '.log');
            write(storage($name, '/log/crontab'), serialize($params));
        } catch (\Throwable $throwable) {
            logger()->addError($throwable, 'throwable');
        } finally {
            $this->after();
        }
    }


    /**
     * @throws \Exception
     */
    private function after(): void
    {
        if ($this->getExecuteNumber() < $this->getMaxExecuteNumber()) {
            $this->recover();
        } else if ($this->isLoop()) {
            $this->recover();
        }
    }


}
