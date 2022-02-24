<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
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

	use Alias;


	/**
	 * @param $cds
	 * @return bool
	 *
	 * db is in transaction
	 * @throws Exception
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
		$connection = Context::getContext($coroutineName);
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

		if (($connection = Context::getContext($coroutineName)) instanceof PDO) {
			return $connection;
		}

		$minx = Config::get('databases.pool.min', 1);

		/** @var PDO $connections */
		$connections = $this->getPool()->get($coroutineName, $this->create($coroutineName, $config), $minx);
		if (Context::hasContext('begin_' . $coroutineName)) {
			$connections->beginTransaction();
		}
		return $connections;
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
		$this->getPool()->push($name, $PDO);
	}


	/**
	 * @param $name
	 * @param $max
	 * @throws Exception
	 */
	public function initConnections($name, $max)
	{
		$this->getPool()->initConnections($name, $max);
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

		$this->getPool()->push($coroutineName, $client);
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
		$this->getPool()->clean($name);
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

}
