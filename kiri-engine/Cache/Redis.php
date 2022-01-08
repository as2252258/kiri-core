<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */
declare(strict_types=1);

namespace Kiri\Cache;

use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Kiri\Pool\Redis as PoolRedis;
use Kiri\Annotation\Inject;
use Server\Events\OnWorkerExit;
use Swoole\Timer;

/**
 * Class Redis
 * @package Kiri\Kiri\Cache
 * @mixin \Redis
 */
class Redis extends Component
{


	const REDIS_OPTION_HOST = 'host';
	const REDIS_OPTION_PORT = 'port';
	const REDIS_OPTION_PREFIX = 'prefix';
	const REDIS_OPTION_AUTH = 'auth';
	const REDIS_OPTION_DATABASES = 'databases';
	const REDIS_OPTION_TIMEOUT = 'timeout';
	const REDIS_OPTION_POOL = 'pool';
	const REDIS_OPTION_POOL_TICK = 'tick';
	const REDIS_OPTION_POOL_MIN = 'min';
	const REDIS_OPTION_POOL_MAX = 'max';


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function init()
	{
		$connections = Kiri::getDi()->get(PoolRedis::class);

		$config = $this->get_config();

		$length = Config::get('cache.redis.pool.max', 10);

		$this->eventProvider->on(OnWorkerExit::class, [$this, 'destroy'], 0);

		$connections->initConnections('Redis:' . $config['host'], true, $length);
	}


	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws
	 */
	public function __call($name, $arguments): mixed
	{
		$time = microtime(true);
		if (method_exists($this, $name)) {
			$data = $this->{$name}(...$arguments);
		} else {
			$data = $this->proxy($name, $arguments);
		}
		if (microtime(true) - $time >= 0.02) {
			$this->warning('Redis:' . Json::encode([$name, $arguments]) . (microtime(true) - $time));
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
        while (!$this->setNx($key, 1)) {
            if (time()- $time >= $timeout) {
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
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function release()
	{
		$connections = Kiri::getDi()->get(PoolRedis::class);
		$connections->release($this->get_config(), true);
	}

	/**
	 * 销毁连接池
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function destroy()
	{
		$connections = Kiri::getDi()->get(PoolRedis::class);
		$connections->connection_clear($this->get_config(), true);
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function proxy($name, $arguments): mixed
	{
		$connections = Kiri::getDi()->get(PoolRedis::class);

		$config = $this->get_config();

		$client = $connections->get($config, true);
		if (!($client instanceof Base\Redis)) {
			throw new Exception('Redis connections more.');
		}
		$response = $client->{$name}(...$arguments);
		$this->release();
		return $response;
	}

	/**
	 * @return array
	 * @throws ConfigException
	 */
	public function get_config(): array
	{
		return Config::get('cache.redis', null, true);
	}

}
