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
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Exception\RedisConnectException;
use Kiri\Pool\Pool;
use Kiri\Server\Events\OnWorkerExit;

/**
 * Class Redis
 * @package Kiri\Cache
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
	 * @param EventProvider $eventProvider
	 * @param Pool $pool
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public EventProvider $eventProvider,
	                            public Pool          $pool, array $config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @return void
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function init(): void
	{
		$config = $this->get_config();

		$length = Config::get('cache.redis.pool.max', 10);

		$this->eventProvider->on(OnWorkerExit::class, [$this, 'destroy'], 0);

		$this->pool->initConnections($config['host'], $length, static function () use ($config) {
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
		});
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
	 * @throws \RedisException
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
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function release()
	{
		$this->pool->clean($this->get_config()['host']);
	}

	/**
	 * 销毁连接池
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function destroy()
	{
		$this->pool->clean($this->get_config()['host']);
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
		$client = $this->getClient();
		try {
			$response = $client->{$name}(...$arguments);
		} catch (\Throwable $throwable) {
			$response = $this->logger->addError($throwable->getMessage());
		} finally {
			$this->pool->push($this->get_config()['host'], $client);
		}
		return $response;
	}


	/**
	 * @return \Redis
	 * @throws ConfigException
	 */
	private function getClient(): \Redis
	{
		$config = $this->get_config();
		return $this->pool->get($config['host']);
	}


	/**
	 * @return array
	 */
	public function get_config(): array
	{
		return Config::get('cache.redis', null, true);
	}

}
