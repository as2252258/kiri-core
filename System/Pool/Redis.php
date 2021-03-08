<?php
declare(strict_types=1);


namespace Snowflake\Pool;


use HttpServer\Http\Context;
use Redis as SRedis;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\RedisConnectException;
use Exception;
use Snowflake\Abstracts\Pool;

/**
 * Class RedisClient
 * @package Snowflake\Snowflake\Pool
 */
class Redis extends Pool
{


    public int $_create = 0;

    /**
     * @param $value
     */
    public function setLength($value)
    {
        $this->max = $value;
    }


    /**
     * @param mixed $config
     * @param bool $isMaster
     * @return mixed
     * @throws Exception
     */
    public function get(mixed $config, $isMaster = false): mixed
    {
        $name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
        $coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
        if (($redis = Context::getContext($coroutineName)) instanceof \Redis) {
            return $redis;
        }
        return Context::setContext($coroutineName, $this->getFromChannel($coroutineName, $config));
    }


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return SRedis
	 * @throws RedisConnectException
	 * @throws ComponentException
	 */
    public function createClient(string $name, mixed $config): SRedis
    {
        $this->printClients($config['host'], $name, true);
        $redis = new SRedis();
        if (!$redis->connect($config['host'], (int)$config['port'], $config['timeout'])) {
            throw new RedisConnectException(sprintf('The Redis Connect %s::%d Fail.', $config['host'], $config['port']));
        }
        if (empty($config['auth']) || !$redis->auth($config['auth'])) {
            throw new RedisConnectException(sprintf('Redis Error: %s, Host %s, Auth %s', $redis->getLastError(), $config['host'], $config['auth']));
        }
        if (!isset($config['read_timeout'])) {
            $config['read_timeout'] = 10;
        }
        $redis->select($config['databases']);
        $redis->setOption(SRedis::OPT_READ_TIMEOUT, $config['read_timeout']);
        $redis->setOption(SRedis::OPT_PREFIX, $config['prefix']);

        $this->_create += 1;

        return $redis;
    }


    /**
     * @param array $config
     * @param bool $isMaster
     */
    public function release(array $config, $isMaster = false)
    {
        $name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
        $coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
        if (!Context::hasContext($coroutineName)) {
            return;
        }
        $this->push($coroutineName, Context::getContext($coroutineName));
        $this->remove($coroutineName);
        $this->lastTime = time();
    }

    /**
     * @param array $config
     * @param bool $isMaster
     * @throws Exception
     */
    public function destroy(array $config, $isMaster = false)
    {
        $name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];
        $coroutineName = $this->name('redis', 'redis:' . $name, $isMaster);
        if (Context::hasContext($coroutineName)) {
	        $this->desc($coroutineName);

	        $this->remove($coroutineName);
        }
        $this->clean($coroutineName);
    }

    /**
     * @param $coroutineName
     */
    public function remove(string $coroutineName)
    {
        Context::remove($coroutineName);
    }

	/**
	 * @param string $name
	 * @param mixed $client
	 * @return bool
	 * @throws Exception
	 */
    public function checkCanUse(string $name, mixed $client): bool
    {
        try {
            if (!($client instanceof SRedis)) {
                $result = false;
            } else if (!$client->isConnected() || !$client->ping('connect.')) {
                $result = false;
            } else {
                $result = true;
            }
        } catch (\Throwable $exception) {
	        $this->addError($exception, 'redis');
            $result = false;
        } finally {
            if (!$result) {
                $this->desc($name);
            }
            return $result;
        }
    }

    /**
     * @param string $name
     */
    public function desc(string $name)
    {
        $this->_create -= 1;
    }


}
