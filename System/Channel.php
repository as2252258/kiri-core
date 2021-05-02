<?php


namespace Snowflake;


use Closure;
use Exception;
use Snowflake\Abstracts\Component;
use SplQueue;


/**
 * Class Channel
 * @package Snowflake
 */
class Channel extends Component
{


    private array $_channels = [];


    /**
     * @param mixed $value
     * @param string $name
     * @throws Exception
     */
    public function push(mixed $value, string $name = ''): void
    {
        $channel = $this->channelInit($name);
        if ($channel->count() >= 100) {
            return;
        }
        $channel->enqueue($value);
    }


    /**
     * @param string $name
     * @return bool|SplQueue
     */
    private function channelInit(string $name = ''): bool|SplQueue
    {
        if (!isset($this->_channels[$name]) || !($this->_channels[$name] instanceof SplQueue)) {
            $this->_channels[$name] = new SplQueue();
        }
        return $this->_channels[$name];
    }


    /**
     *
     * 清空缓存
     */
    public function cleanAll()
    {
        /** @var SplQueue $channel */
        foreach ($this->_channels as $channel) {
            if (!($channel instanceof SplQueue)) {
                continue;
            }
            while ($channel->count() > 0) {
                $channel->dequeue();
            }
        }
        $this->_channels = [];
    }

    /**
     * @param $timeout
     * @param Closure $closure
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function pop(string $name, Closure $closure): mixed
    {
        $channel = $this->channelInit($name);
        if ($channel->isEmpty()) {
            return call_user_func($closure);
        }
        return $channel->dequeue();
    }


}
