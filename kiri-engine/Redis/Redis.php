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
use JetBrains\PhpStorm\ArrayShape;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Events\EventProvider;
use Kiri\Di\Inject\Container;
use Kiri\Exception\ConfigException;
use Kiri\Exception\RedisConnectException;
use Kiri\Pool\Pool;
use Kiri\Server\Events\OnWorkerExit;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use ReflectionException;

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
        Kiri::configure($this, \config('redis', []));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function init(): void
    {
        on(OnWorkerExit::class, [$this, 'destroy']);
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
            $data = $this->{$name}(...$arguments);
        } else {
            $data = $this->proxy($name, $arguments);
        }
        return $data;
    }


    /**
     * @param $key
     * @param int $timeout
     * @return bool
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
     * @throws Exception
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
     * @throws Exception
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
        $this->pool()->clean($this->host);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function proxy($name, $arguments): mixed
    {
        $client = $this->getClient();
        try {
            $response = $client->{$name}(...$arguments);
        } catch (\Throwable $throwable) {
            $response = trigger_print_error($throwable, 'redis');
        } finally {
            $this->pool()->push($this->host, $client);
        }
        return $response;
    }


    /**
     * @return \Redis
     * @throws ReflectionException
     */
    private function getClient(): \Redis
    {
        return $this->pool()->get($this->host);
    }


    /**
     * @return Pool
     * @throws ReflectionException
     */
    protected function pool(): Pool
    {
        $pool = Kiri::getPool();
        if (!$pool->hasChannel($this->host)) {
            $config = $this->get_config();
            $length = \config('cache.redis.pool.max', 10);
            $pool->created($config['host'], $length, $this->connect($config));
        }
        return $pool;
    }


    /**
     * @param $config
     * @return \Closure
     */
    protected function connect($config): \Closure
    {
        return static function () use ($config) {
            $redis = new \Redis();
            if (!$redis->connect($config['host'], $config['port'], $config['timeout'])) {
                throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
            }
            if (!empty($config['auth']) && !$redis->auth($config['auth'])) {
                throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
            }
            if ($config['read_timeout'] < 0) {
                $config['read_timeout'] = 0;
            }
            $redis->select($config['databases']);
            if ($config['read_timeout'] > 0) {
                $redis->setOption(\Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
            }
            $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);
            return $redis;
        };
    }


    /**
     * @return array
     */
    #[ArrayShape(['host' => "string", 'port' => "int", 'prefix' => "string", 'auth' => "string", 'databases' => "int", 'timeout' => "int", 'read_timeout' => "int", 'pool' => "array|int[]"])]
    public function get_config(): array
    {
        return [
            'host'         => $this->host,
            'port'         => $this->port,
            'prefix'       => $this->prefix,
            'auth'         => $this->auth,
            'databases'    => $this->databases,
            'timeout'      => $this->timeout,
            'read_timeout' => $this->read_timeout,
            'pool'         => $this->pool
        ];
    }

}
