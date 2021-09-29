<?php
declare(strict_types=1);


namespace Kiri\Pool;


use Annotation\Inject;
use Closure;
use Exception;
use Http\Handler\Context;
use Kiri\Abstracts\Component;
use Kiri\Events\EventProvider;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Events\OnWorkerExit;

/**
 * Class RedisClient
 * @package Kiri\Kiri\Pool
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
	 * @return Closure
	 */
	public function create(string $name, mixed $config): Closure
	{
		return static function () use ($name, $config) {
			return Kiri::getDi()->create(\Kiri\Cache\Base\Redis::class, [
				$config['host'], (int)$config['port'], $config['databases'] ?? 0,
				$config['auth'], $config['prefix'] ?? '', $config['timeout'] ?? 30,
				$config['read_timeout'] ?? 30
			]);
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
		$this->getPool()->clean($coroutineName);
		Context::remove($coroutineName);
	}


	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function connection_clear(array $config, bool $isMaster = false)
	{
		$coroutineName = $this->name('Redis:' . $config['host'], $isMaster);
		$this->getPool()->clean($coroutineName);
	}


	/**
	 * @return Pool
	 * @throws Exception
	 */
	public function getPool(): Pool
	{
		return Kiri::getDi()->get(Pool::class);
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
