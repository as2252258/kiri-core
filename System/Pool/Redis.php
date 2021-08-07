<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use Exception;
use HttpServer\Http\Context;
use Redis as SRedis;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\RedisConnectException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Runtime;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Component
{

    use Alias;


    /**
     * @param mixed $config
     * @param bool $isMaster
     * @return mixed
     * @throws Exception
     */
    public function get(mixed $config, bool $isMaster = false): mixed
    {
        $coroutineName = $this->name('Redis:' . $config['host'], $isMaster);
        if (Context::hasContext($coroutineName)) {
            return Context::getContext($coroutineName);
        }
        $clients = $this->getPool()->get($coroutineName, $this->create($coroutineName, $config));
        return Context::setContext($coroutineName, $clients);
    }


    /**
     * @param string $name
     * @param mixed $config
     * @return SRedis
     * @throws RedisConnectException
     * @throws Exception
     */
    public function create(string $name, mixed $config): \Closure
    {
        return static function () use ($name, $config) {
            if (Coroutine::getCid() === -1) {
                Runtime::enableCoroutine(false);
            }
            $redis = new SRedis();
            if (!$redis->pconnect($config['host'], (int)$config['port'], $config['timeout'])) {
                throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
            }
            if (!empty($config['auth']) && !$redis->auth($config['auth'])) {
                throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
            }
            if (!isset($config['read_timeout'])) {
                $config['read_timeout'] = 10;
            }
            $redis->select($config['databases']);
            $redis->setOption(SRedis::OPT_READ_TIMEOUT, $config['read_timeout']);
            $redis->setOption(SRedis::OPT_PREFIX, $config['prefix']);
            return $redis;
        };
    }


    /**
     * @param array $config
     * @param bool $isMaster
     * @throws ConfigException
     * @throws Exception
     */
    public function release(array $config, bool $isMaster = false)
    {
        $coroutineName = $this->name('Redis:' . $config['host'], $isMaster);
        if (!Context::hasContext($coroutineName)) {
            return;
        }

        $this->getPool()->push($coroutineName, Context::getContext($coroutineName));
        Context::remove($coroutineName);
    }

    /**
     * @param array $config
     * @param bool $isMaster
     * @throws Exception
     */
    public function destroy(array $config, bool $isMaster = false)
    {
        $coroutineName = $this->name('Redis:' . $config['host'], $isMaster);
        if (Context::hasContext($coroutineName)) {
            $this->getPool()->decrement($coroutineName);
        }
        $this->getPool()->clean($coroutineName);
        Context::remove($coroutineName);
    }


    /**
     * @return Pool
     * @throws Exception
     */
    public function getPool(): Pool
    {
        return Snowflake::getDi()->get(Pool::class);
    }


    /**
     * @param $name
     * @param $isMaster
     * @param $max
     * @throws Exception
     */
    public function initConnections($name, $isMaster, $max)
    {
        $this->getPool()->initConnections($name, $isMaster, $max);
    }


}
