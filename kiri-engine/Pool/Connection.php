<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Closure;
use Database\Mysql\PDO;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Context;
use Kiri\Kiri;
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
	public function inTransaction($cds): bool
	{
		$name = $this->name('Mysql:' . $cds, true);
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
		$coroutineName = $this->name('Mysql:' . $coroutineName, true);
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
		$coroutineName = $this->name('Mysql:' . $coroutineName, true);
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
		$coroutineName = $this->name('Mysql:' . $coroutineName, true);
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO) {
			$connection->rollBack();
		}
	}


	/**
	 * @param mixed $config
	 * @param bool $isMaster
	 * @return PDO|null
	 * @throws Exception
	 */
	public function get(mixed $config, bool $isMaster = false): ?PDO
	{
		$coroutineName = $this->name('Mysql:' . $config['cds'], $isMaster);
		if (($pdo = Context::getContext($coroutineName)) instanceof PDO) {
			return $pdo;
		}

		$minx = Config::get('databases.pool.min', 1);

		/** @var PDO $connections */
		$connections = $this->getPool()->get($coroutineName, $this->create($coroutineName, $config), $minx);
		if (Context::hasContext('begin_' . $coroutineName)) {
			$connections->beginTransaction();
		}
		return Context::setContext($coroutineName, $connections);
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
	 * @param $name
	 * @param $isMaster
	 * @param $max
	 * @throws Exception
	 */
	public function initConnections($name, $isMaster, $max)
	{
		$this->getPool()->initConnections($name, $isMaster, $max);
	}


	/**
	 * @param $coroutineName
	 * @param $isMaster
	 * @throws Exception
	 */
	public function release($coroutineName, $isMaster)
	{
		$coroutineName = $this->name('Mysql:' . $coroutineName, $isMaster);
		/** @var PDO $client */
		if (!($client = Context::getContext($coroutineName)) instanceof PDO) {
			return;
		}
		if ($client->inTransaction()) {
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
	public function connection_clear($name, $isMaster)
	{
		$this->getPool()->clean($this->name($name, $isMaster));
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
		} catch (Error | Throwable $exception) {
			$result = $this->addError($exception, 'mysql');
		} finally {
			return $result;
		}
	}


	/**
	 * @param $coroutineName
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function disconnect($coroutineName, bool $isMaster = false)
	{
		Context::remove($coroutineName);
		$coroutineName = $this->name('Mysql:' . $coroutineName, $isMaster);
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
