<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */
declare(strict_types=1);

namespace Kiri\Redis;

use Exception;
use Kiri;
use Kiri\Exception\RedisConnectException;
use Kiri\Pool\Pool;
use RedisException;
use function config;

/**
 * Class Redis
 * @package Kiri\Cache
 * @mixin \Redis
 */
class Redis
{

    public string $host = '';

    public int $port = 6379;

    public string $prefix = 'api:';

    public string $auth = '';

    public int $databases = 0;

    public int $timeout = 30;


    /**
     * @var int
     */
    public int $read_timeout = -1;

    /**
     * @var array|int[]
     */
    public array $pool = ['min' => 1, 'max' => 100];


    /**
     * 初始化
     */
    public function __construct()
    {
        Kiri::configure($this, config('redis', []));
    }

    /**
     * @return void
     * @throws
     */
    public function init(): void
    {
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws
     */
    public function __call($name, $arguments): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        } else {
            return $this->proxy($name, $arguments);
        }
    }


    /**
     * @param $key
     * @param int $timeout
     * @return bool
     * @throws
     */
    public function waite($key, int $timeout = 5): bool
    {
        $time = time();
        while (!$this->setNx($key, '1')) {
            if (time() - $time >= $timeout) {
                return FALSE;
            }
            usleep(1000);
        }
        $this->expire($key, $timeout);
        return TRUE;
    }


    /**
     * @param $key
     * @param int $timeout
     * @return bool|int
     * @throws
     */
    public function lock($key, int $timeout = 5): bool|int
    {
        $script = <<<SCRIPT
local _nx = redis.call('setnx',KEYS[1], ARGV[1])
if (_nx ~= 0) then
	redis.call('expire',KEYS[1], ARGV[1])
	return 1
end
return 0
SCRIPT;
        return $this->eval($script, ['{lock}:' . $key, $timeout], 1);
    }


    /**
     * @param $key
     * @return int
     * @throws
     */
    public function unlock($key): int
    {
        return $this->del('{lock}:' . $key);
    }


    /**
     * @return void
     * @throws
     */
    public function destroy(): void
    {
        $this->pool()->close($this->host);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws
     */
    public function proxy($name, $arguments): mixed
    {
        $client = $this->getClient();
        try {
            return $client->{$name}(...$arguments);
        } catch (\Throwable $throwable) {
            return trigger_print_error(throwable($throwable));
        } finally {
            if ($client->ping('h') == 'h') {
                $this->pool()->push($this->host, $client);
            }
        }
    }


    /**
     * @return \Redis
     * @throws
     */
    private function getClient(): \Redis
    {
        return $this->pool()->get($this->host);
    }


    /**
     * @return Pool
     * @throws
     */
    protected function pool(): Pool
    {
        $pool = Kiri::getPool();
        if (!$pool->hasChannel($this->host)) {
            $pool->created($this->host, $this->pool['max'], [$this, 'connect']);
        }
        return $pool;
    }


    /**
     * @return \Redis
     * @throws
     */
    protected function connect(): \Redis
    {
        $redis = new \Redis();
        if (!$redis->connect($this->host, $this->port, $this->timeout)) {
            throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $this->host, $this->port));
        }
        if (!empty($this->auth) && !$redis->auth($this->auth)) {
            throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $this->host, $this->auth));
        }
        $redis->select($this->databases);
        if ($this->read_timeout > 0) {
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->read_timeout);
        }
        if (!empty($this->prefix)) {
            $redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
        }
        return $redis;
    }
}
