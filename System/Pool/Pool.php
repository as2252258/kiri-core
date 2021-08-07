<?php


namespace Snowflake\Pool;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;


/**
 * Class Pool
 * @package Snowflake\Pool
 */
class Pool extends Component
{

    /** @var Channel[] */
    private static array $_connections = [];

    public int $max = 60;

    public int $creates = -1;

    private array $_times = [];

    use Alias;


    /**
     * @return array
     * @throws ConfigException
     */
    private function getClearTime(): array
    {
        $firstClear = Config::get('pool.clear.start', 600);
        $lastClear = Config::get('pool.clear.end', 300);
        return [$firstClear, $lastClear];
    }


    /**
     * @throws Exception
     */
    public function Heartbeat_detection($ticker)
    {
        if (env('state') == 'exit') {
            Timer::clear($this->creates);
            foreach (static::$_connections as $channel) {
                $this->flush($channel, 0);
                $channel->close();
            }
            static::$_connections = [];
            $this->creates = -1;
        } else {
            $this->heartbeat_flush();
        }
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    private function heartbeat_flush()
    {
        $num = [];
        $total = 0;
        $min = Config::get('databases.pool.min', 1);
        foreach (static::$_connections as $key => $channel) {
            if (!isset($num[$key])) {
                $num[$key] = 0;
            }
            if (time() - ($this->_times[$key] ?? time()) > 120) {
                $this->flush($channel, 0);
            } else if ($channel->length() > $min) {
                $this->flush($channel, $min);
            }
            $num[$key] += ($length = $channel->length());
            $total += $length;
        }
        $this->clear($total, $num);
    }


    /**
     * @param $total
     * @throws \Exception
     */
    private function clear($total, $num)
    {
        write(var_export($num, true), 'connections');
        if ($total >= 1) {
            return;
        }
        Timer::clear($this->creates);
        if (Snowflake::isWorker() || Snowflake::isTask()) {
            $this->debug('Worker #' . env('worker') . ' clear time tick.');
        }
        $this->creates = -1;
    }


    /**
     * @param $channel
     * @param $retain_number
     * @throws Exception
     */
    public function flush($channel, $retain_number)
    {
        $this->pop($channel, $retain_number);
    }


    /**
     * @param Channel $channel
     * @param $retain_number
     * @throws Exception
     */
    protected function pop(Channel $channel, $retain_number): void
    {
        if (Coroutine::getCid() === -1) {
            return;
        }
        while ($channel->length() > $retain_number) {
            $connection = $channel->pop();
            if ($connection) {
                unset($connection);
            }
        }
    }


    /**
     * @param $name
     * @param false $isMaster
     * @param int $max
     */
    public function initConnections($name, bool $isMaster = false, int $max = 60)
    {
        $name = $this->name($name, $isMaster);
        if (isset(static::$_connections[$name]) && static::$_connections[$name] instanceof Channel) {
            return;
        }
        if (Coroutine::getCid() === -1) {
            return;
        }
        if ($this->creates === -1) {
            $this->creates = Timer::tick(60000, [$this, 'Heartbeat_detection']);
        }
        static::$_connections[$name] = new Channel($max);
        $this->max = $max;
    }


    /**
     * @param $name
     * @return Channel
     * @throws ConfigException
     * @throws Exception
     */
    private function getChannel($name): Channel
    {
        if (!isset(static::$_connections[$name])) {
            static::$_connections[$name] = new Channel(Config::get('databases.pool.max', 10));
        }
        if (static::$_connections[$name]->errCode == SWOOLE_CHANNEL_CLOSED) {
            throw new Exception('Channel is Close.');
        }
        if ($this->creates === -1) {
            $this->creates = Timer::tick(60000, [$this, 'Heartbeat_detection']);
        }
        return static::$_connections[$name];
    }


    /**
     * @param $name
     * @return array
     * @throws Exception
     */
    public function get($name, $callback): mixed
    {
        if (Coroutine::getCid() === -1) {
            return $callback();
        }
        $this->_times[$name] = time();
        $channel = $this->getChannel($name);
        if (!$channel->isEmpty()) {
            $connection = $channel->pop();
            if ($this->checkCanUse($name, $connection)) {
                return $connection;
            }
        }
        return $callback();
    }


    /**
     * @param $name
     * @return bool
     * @throws \Snowflake\Exception\ConfigException
     */
    public function isNull($name): bool
    {
        return $this->getChannel($name)->isEmpty();
    }


    /**
     * @param string $name
     * @param mixed $client
     * @return bool
     * 检查连接可靠性
     */
    public function checkCanUse(string $name, mixed $client): bool
    {
        return true;
    }


    /**
     * @param string $name
     * @return bool
     */
    public function hasItem(string $name): bool
    {
        if (isset(static::$_connections[$name])) {
            return !static::$_connections[$name]->isEmpty();
        }
        return false;
    }


    /**
     * @param string $name
     * @return mixed
     */
    public function size(string $name): mixed
    {
        if (Coroutine::getCid() === -1) {
            return 0;
        }
        if (!isset(static::$_connections[$name])) {
            return 0;
        }
        return static::$_connections[$name]->length();
    }


    /**
     * @param string $name
     * @param mixed $client
     * @throws ConfigException
     */
    public function push(string $name, mixed $client)
    {
        if (Coroutine::getCid() === -1) {
            return;
        }
        $channel = $this->getChannel($name);
        if (!$channel->isFull()) {
            $channel->push($client);
        }
        unset($client);
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function clean(string $name)
    {
        if (Coroutine::getCid() === -1 || !isset(static::$_connections[$name])) {
            return;
        }
        $channel = static::$_connections[$name];
        $this->pop($channel, 0);
    }


    /**
     * @return Channel[]
     */
    protected function getChannels(): array
    {
        return static::$_connections;
    }


}
