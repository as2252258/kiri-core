<?php


namespace Kiri\Pool;


use Exception;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;


/**
 * Class Pool
 * @package Kiri\Pool
 */
class Pool extends Component
{

    /** @var Channel[] */
    private static array $_connections = [];

    public int $max = 60;

    use Alias;


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
        return static::$_connections[$name];
    }


	/**
	 * @param $name
	 * @param $callback
	 * @return array
	 * @throws ConfigException
	 */
    public function get($name, $callback): mixed
    {
        if (Coroutine::getCid() === -1) {
            return $callback();
        }
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
     * @throws ConfigException
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
