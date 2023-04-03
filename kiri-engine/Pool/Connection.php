<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
use Database\Db;
use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Di\Context;
use Kiri\Exception\ConfigException;
use PDO;
use Swoole\Error;
use Throwable;

/**
 * Class Connection
 * @package Kiri\Pool
 */
class Connection extends Component
{


	private array $master = [];

	private int $total = 0;


	/**
	 * @param Pool $pool
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public Pool $pool, array $config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function inTransaction($name): bool
	{
		$connection = Context::get($name);
		if ($connection instanceof \Database\Mysql\PDO) {
			return $connection->inTransaction();
		}
		return false;
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function beginTransaction($coroutineName)
	{
		$connection = $this->get($coroutineName);
		if ($connection instanceof \Database\Mysql\PDO) {
			$connection->beginTransaction();
		}
	}

	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function commit($coroutineName)
	{
		$connection = Context::get($coroutineName);
		if ($connection instanceof \Database\Mysql\PDO) {
			$connection->commit();
		}
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function rollback($coroutineName)
	{
		$connection = Context::get($coroutineName);
		if ($connection instanceof \Database\Mysql\PDO) {
			$connection->rollBack();
		}
	}


	/**
	 * @param string $cds
	 * @return PDO|null
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get(string $cds): ?\Database\Mysql\PDO
	{
		if (!$this->pool->hasChannel($cds)) {
			throw new Exception('Queue not exists.');
		}
		return $this->pool->get($cds);
	}


	/**
	 * @param string $name
	 * @return array
	 */
	public function check(string $name): array
	{
		return $this->pool->check($name);
	}


	/**
	 * @param $config
	 * @return Closure
	 */
	public function create($config): Closure
	{
		return function () use ($config) {
			if ($this->total >= 300) {
				$connect = $this->pool->waite($config['cds'], 60);
				if (!($connect instanceof \Database\Mysql\PDO)) {
					throw new Exception("Get database link wait timeout.");
				}
			}
			$this->total += 1;
			return new \Database\Mysql\PDO($config);
		};
	}


	/**
	 * @param string $name
	 * @param \Database\Mysql\PDO $PDO $PDO
	 * @return void
	 * @throws ConfigException
	 */
	public function addItem(string $name, \Database\Mysql\PDO $PDO): void
	{
		$this->pool->push($name, $PDO);
	}


	/**
	 * @param array $config
	 * @param int $max
	 */
	public function initConnections(array $config, int $max)
	{
		$this->pool->initConnections($config['cds'], $max, function () use ($config) {
			return new \Database\Mysql\PDO($config);
		});
	}


	/**
	 * @param $coroutineName
	 * @throws Kiri\Exception\ConfigException
	 * @throws Exception
	 */
	public function release($coroutineName)
	{
		$client = Context::get($coroutineName);
		if (!($client instanceof \Database\Mysql\PDO) || $client->inTransaction()) {
			return;
		}

		$this->pool->push($coroutineName, $client);
		Context::remove($coroutineName);
	}


	/**
	 * @throws Exception
	 */
	public function flush($coroutineName, $minNumber = 1)
	{
		$this->pool->flush($coroutineName, $minNumber);
	}


	/**
	 * @param $coroutineName
	 * @return bool
	 */
	private function hasClient($coroutineName): bool
	{
		return Context::exists($coroutineName);
	}


	/**
	 * batch release
	 * @throws Exception
	 */
	public function connection_clear($name)
	{
		$this->pool->clean($name);
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
			if (empty($client) || !($client instanceof PDO)) {
				$result = false;
			} else {
				$result = true;
			}
		} catch (Error|Throwable $exception) {
			$result = $this->logger->addError($exception, 'mysql');
		} finally {
			return $result;
		}
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function disconnect($coroutineName)
	{
		Context::remove($coroutineName);
		$this->pool->clean($coroutineName);
	}


}
