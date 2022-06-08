<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
use Database\Db;
use Database\Mysql\PDO;
use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Context;
use Swoole\Error;
use Throwable;

/**
 * Class Connection
 * @package Kiri\Pool
 */
class Connection extends Component
{


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
		$connection = Context::getContext($name);
		if ($connection instanceof PDO) {
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
		if ($connection instanceof PDO) {
			$connection->beginTransaction();
		}
	}

	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function commit($coroutineName)
	{
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO) {
			$connection->commit();
		}
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function rollback($coroutineName)
	{
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO) {
			$connection->rollBack();
		}
	}


	/**
	 * @param mixed $config
	 * @return PDO|null
	 * @throws Exception
	 */
	public function get(mixed $config): ?PDO
	{
		$coroutineName = $config['cds'];
		$minx = Config::get('databases.pool.min', 1);
		return $this->pool->get($coroutineName, static function () use ($coroutineName, $config) {
			$connect = Kiri::getDi()->create(PDO::class, [$config]);
			if (!Db::inTransactionsActive()) {
				return $connect;
			}
			if (!$connect->inTransaction()) {
				$connect->beginTransaction();
			}
			return $connect;
		}, $minx);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return Closure
	 */
	public function create($coroutineName, $config): Closure
	{
		return static function () use ($coroutineName, $config) {
			return Kiri::getDi()->create(PDO::class, [$config]);
		};
	}


	/**
	 * @param string $name
	 * @param PDO $PDO
	 * @return void
	 * @throws Kiri\Exception\ConfigException
	 * @throws Exception
	 */
	public function addItem(string $name, PDO $PDO)
	{
		$this->pool->push($name, $PDO);
	}


	/**
	 * @param $name
	 * @param $max
	 * @throws Exception
	 */
	public function initConnections($name, $max)
	{
		$this->pool->initConnections($name, $max);
	}


	/**
	 * @param $coroutineName
	 * @throws Kiri\Exception\ConfigException
	 * @throws Exception
	 */
	public function release($coroutineName)
	{
		$client = Context::getContext($coroutineName);
		if (!($client instanceof PDO) || $client->inTransaction()) {
			return;
		}

		$this->pool->push($coroutineName, $client);
		Context::remove($coroutineName);
	}


	/**
	 * @param $coroutineName
	 * @return bool
	 */
	private function hasClient($coroutineName): bool
	{
		return Context::hasContext($coroutineName);
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
