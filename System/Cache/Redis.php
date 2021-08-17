<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 11:00
 */
declare(strict_types=1);

namespace Kiri\Cache;

use Annotation\Inject;
use Exception;
use Server\Events\OnWorkerExit;
use Server\Events\OnWorkerStop;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Core\Json;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Kiri\Pool\Redis as PoolRedis;

/**
 * Class Redis
 * @package Kiri\Kiri\Cache
 * @mixin \Redis
 */
class Redis extends Component
{

	/**
	 * @var EventProvider
	 */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function init()
	{
        $connections = Kiri::getDi()->get(PoolRedis::class);

		$config = $this->get_config();

		$length = Config::get('connections.pool.max', 10);

		$this->eventProvider->on(OnWorkerStop::class, [$this, 'destroy'], 0);
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
			$data = $this->proxy()->{$name}(...$arguments);
			$this->release();
		}
		if (microtime(true) - $time >= 0.02) {
			$this->warning('Redis:' . Json::encode([$name, $arguments]) . (microtime(true) - $time));
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
	 * @return \Redis
	 * @throws Exception
	 */
	public function proxy(): \Redis
	{
        $connections = Kiri::getDi()->get(PoolRedis::class);

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
