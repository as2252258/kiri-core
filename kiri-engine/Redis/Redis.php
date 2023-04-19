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
class Redis extends Component
{

	private string $host = '';

	private int $port = 6379;

	private string $prefix = 'api:';

	private string $auth = '';

	private int $databases = 0;

	private int $timeout = 30;


	/**
	 * @var int
	 */
	private int $read_timeout = -1;

	/**
	 * @var array|int[]
	 */
	private array $pool = ['min' => 1, 'max' => 100];


	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws Exception
	 */
	public function init(): void
	{
		$config = $this->get_config();

		$length = Config::get('cache.redis.pool.max', 10);
		on(OnWorkerExit::class, [$this, 'destroy']);
		Kiri::getPool()->initConnections($config['host'], $length, static function () use ($config) {
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
	 * @throws RedisException
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
		Kiri::getPool()->clean($this->host);
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
			$response = addError($throwable, 'redis');
		} finally {
			Kiri::getPool()->push($this->host, $client);
		}
		return $response;
	}


	/**
	 * @return \Redis
	 * @throws ConfigException
	 * @throws ReflectionException
	 */
	private function getClient(): \Redis
	{
		return Kiri::getPool()->get($this->host);
	}


	/**
	 * @return array
	 */
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
