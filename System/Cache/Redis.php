<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */
declare(strict_types=1);

namespace Snowflake\Cache;

use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class Redis
 * @package Snowflake\Snowflake\Cache
 * @see \Redis
 *
 */
class Redis extends Component
{
	public string $host = '127.0.0.1';
	public string $auth = 'xl.2005113426';
	public int $port = 6973;
	public int $databases = 0;
	public int $timeout = -1;
	public string $prefix = 'idd';

	/**
	 * @throws Exception
	 */
	public function init()
	{
		Event::on(Event::SYSTEM_RESOURCE_CLEAN, [$this, 'destroy']);
		Event::on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'release']);
		Event::on(Event::SERVER_WORKER_START, [$this, 'createPool']);
		Event::on(Event::SERVER_TASK_START, [$this, 'createPool']);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function createPool()
	{
		$connections = Snowflake::app()->getRedisFromPool();

		$config = $this->get_config();
		$name = $config['host'] . ':' . $config['prefix'] . ':' . $config['databases'];

		$length = env('REDIS.POOL_LENGTH', 100);

		$connections->initConnections('redis', 'redis:' . $name, true, $length);
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
			$data = $this->proxy()->{$name}(...$arguments);
		}

		if (microtime(true) - $time >= 0.02) {
			$this->warning('Redis:' . Json::encode([$name, $arguments]));
		}

		return $data;
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
		return $this->proxy()->eval($script, ['{lock}:' . $key, $timeout], 1);
	}


	/**
	 * @param $key
	 * @return int
	 * @throws Exception
	 */
	public function unlock($key): int
	{
		$redis = $this->proxy();
		return $redis->del('{lock}:' . $key);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function release()
	{
		$connections = Snowflake::app()->getRedisFromPool();
		$connections->release($this->get_config(), true);
	}

	/**
	 * 销毁连接池
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function destroy()
	{
		$connections = Snowflake::app()->getRedisFromPool();
		$connections->destroy($this->get_config(), true);
	}

	/**
	 * @return \Redis
	 * @throws Exception
	 */
	public function proxy(): \Redis
	{
		$connections = Snowflake::app()->getRedisFromPool();

		$config = $this->get_config();

		$client = $connections->get($config, true);
		if (!($client instanceof \Redis)) {
			throw new Exception('Redis connections more.');
		}
		return $client;
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
