<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;

use HttpServer\Http\Context;
use JetBrains\PhpStorm\Pure;
use PDO;
use Redis;
use Snowflake\Pool\Timeout;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

/**
 * Class Pool
 * @package Snowflake\Snowflake\Pool
 */
abstract class Pool extends Component
{

    /** @var Channel[] */
    private array $_items = [];

    protected int $max = 60;

    use Timeout;

    /**
     * @param $driver
     * @param $name
     * @param false $isMaster
     * @param int $max
     */
    public function initConnections($driver, $name, $isMaster = false, $max = 60)
    {
        $name = $this->name($driver, $name, $isMaster);
        if (isset($this->_items[$name]) && $this->_items[$name] instanceof Channel) {
            return;
        }
        if (!Context::inCoroutine()) {
            return;
        }
        $this->_items[$name] = new Channel((int)$max);
        $this->max = (int)$max;
    }


    /**
     * @param $name
     * @param array $callback
     * @return array
     * @throws Exception
     */
    protected function get($name, array $callback): mixed
    {
        if (!Context::inCoroutine()) {
            return $this->createClient($name, $callback);
        }
        if (!$this->hasItem($name)) {
            if ($this->creates === -1) {
                $this->creates = Timer::tick(1000, [$this, 'Heartbeat_detection']);
            }
            if (!Context::hasContext('create::client::ing::' . $name)) {
                $this->push($name, $this->createClient($name, $callback));
                Context::deleteContext('create::client::ing::' . $name);
            }
        }
        $connection = $this->_items[$name]->pop(-1);
        if (!$this->checkCanUse($name, $connection)) {
            return $this->createClient($name, $callback);
        } else {
            return $connection;
        }
    }


    /**
     * @param $cds
     * @param $coroutineName
     * @param false $isBefore
     */
    public function printClients($cds, $coroutineName, $isBefore = false)
    {
        $this->warning(($isBefore ? 'before ' : '') . 'create client[address: ' . $cds . ', ' . env('workerId') . ', coroutine: ' . Coroutine::getCid() . ', has num: ' . $this->size($coroutineName) . ', has create: ' . $this->_create . ']');
    }


    abstract public function createClient(string $name, array $config): mixed;


    /**
     * @param $driver
     * @param $cds
     * @param false $isMaster
     * @return string
     */
    #[Pure] public function name($driver, $cds, $isMaster = false): string
    {
        if ($isMaster === true) {
            return $cds . '_master';
        } else {
            return $cds . '_slave';
        }
    }


    /**
     * @param $name
     * @param $time
     * @param $client
     * @return mixed
     * 检查连接可靠性
     * @throws Exception
     */
    public function checkCanUse(string $name, mixed $client): mixed
    {
        return true;
    }

    /**
     * @param $name
     * @throws Exception
     */
    public function desc(string $name)
    {
        throw new Exception('Undefined system processing function.');
    }

    /**
     * @param array $config
     * @param $isMaster
     * @throws Exception
     */
    public function getConnection(array $config, bool $isMaster)
    {
        throw new Exception('Undefined system processing function.');
    }


    /**
     * @param $name
     * @return bool
     */
    public function hasItem(string $name): bool
    {
        if (isset($this->_items[$name])) {
            return !$this->_items[$name]->isEmpty();
        }
        return false;
    }


    /**
     * @param $name
     * @return mixed
     */
    public function size(string $name): mixed
    {
        if (!Context::inCoroutine()) {
            return 0;
        }
        if (!isset($this->_items[$name])) {
            return 0;
        }
        return $this->_items[$name]->length();
    }


    /**
     * @param $name
     * @param $client
     */
    public function push(string $name, mixed $client)
    {
        if (!Context::inCoroutine()) {
            return;
        }
        if (!isset($this->_items[$name])) {
            $this->_items[$name] = new Channel($this->max);
        }
        if (!$this->_items[$name]->isFull()) {
            $this->_items[$name]->push($client);
        }
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function clean(string $name)
    {
        if (!Context::inCoroutine()) {
            return;
        }
        if (!isset($this->_items[$name])) {
            return;
        }
        $channel = $this->_items[$name];
        while ($client = $channel->pop(0.001)) {
            unset($client);
            $this->desc($name);
        }
    }


    /**
     * @return Channel[]
     */
    protected function getChannels(): array
    {
        return $this->_items;
    }


}
