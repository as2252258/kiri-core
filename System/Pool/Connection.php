<?php
declare(strict_types=1);

namespace Snowflake\Pool;

use Exception;
use HttpServer\Http\Context;
use PDO;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Error;
use Swoole\Runtime;
use Throwable;

/**
 * Class Connection
 * @package Snowflake\Pool
 */
class Connection extends Component
{

	private ?ClientsPool $clientsPool = null;


	/**
	 * @param $cds
	 * @return bool
	 *
	 * db is in transaction
	 * @throws Exception
	 */
	public function inTransaction($cds): bool
	{
		return Context::getContext('begin_' . $this->getPool()->name('Mysql:' . $cds, true)) == 0;
	}

	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function beginTransaction($coroutineName)
	{
		$coroutineName = $this->getPool()->name('Mysql:' . $coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			Context::setContext('begin_' . $coroutineName, 0);
		}
		Context::increment('begin_' . $coroutineName);
		if (Context::getContext('begin_' . $coroutineName) != 0) {
			return;
		}
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO && !$connection->inTransaction()) {
			$connection->beginTransaction();
		}
	}

	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function commit($coroutineName)
	{
		$coroutineName = $this->getPool()->name('Mysql:' . $coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			return;
		}
		if (Context::decrement('begin_' . $coroutineName) > 0) {
			return;
		}
		$connection = Context::getContext($coroutineName);
		if (!($connection instanceof PDO)) {
			return;
		}
		Context::setContext('begin_' . $coroutineName, 0);
		if ($connection->inTransaction()) {
			$connection->commit();
		}
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function rollback($coroutineName)
	{
		$coroutineName = $this->getPool()->name('Mysql:' . $coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			return;
		}
		if (Context::decrement('begin_' . $coroutineName) > 0) {
			return;
		}
		if (($connection = Context::getContext($coroutineName)) instanceof PDO) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}
		}
		Context::setContext('begin_' . $coroutineName, 0);
	}


	/**
	 * @param mixed $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function get(mixed $config, bool $isMaster = false): mixed
	{
		$coroutineName = $this->getPool()->name('Mysql:' . $config['cds'], $isMaster);
		if (($pdo = Context::getContext($coroutineName)) instanceof PDO) {
			return $pdo;
		}
		if (Coroutine::getCid() === -1) {
			$connections = $this->createClient($coroutineName, $config);
		} else {
			/** @var PDO $connections */
			$connections = $this->getPool()->getFromChannel($coroutineName);
			if (empty($connections)) {
				$connections = $this->createClient($coroutineName, $config);
			}
		}
		if ($number = Context::getContext('begin_' . $coroutineName, Coroutine::getCid())) {
			$number > 0 && $connections->beginTransaction();
		}
		return Context::setContext($coroutineName, $connections);
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
	 * @param string $name
	 * @param mixed $config
	 * @return PDO
	 * @throws Exception
	 */
	public function createClient(string $name, mixed $config): PDO
	{
		if (Coroutine::getCid() === -1) {
			Runtime::enableCoroutine(false);
		}
		$cds = 'mysql:dbname=' . $config['database'] . ';host=' . $config['cds'];
		$link = new PDO($cds, $config['username'], $config['password'], [
			PDO::ATTR_EMULATE_PREPARES         => false,
			PDO::ATTR_CASE                     => PDO::CASE_NATURAL,
			PDO::ATTR_TIMEOUT                  => 60,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES ' . ($config['charset'] ?? 'utf8mb4')
		]);
		$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
		$link->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
		return $link;
	}


	/**
	 * @param $coroutineName
	 * @param $isMaster
	 * @throws Exception
	 */
	public function release($coroutineName, $isMaster)
	{
		$coroutineName = $this->getPool()->name('Mysql:' . $coroutineName, $isMaster);

		/** @var PDO $client */
		if (!($client = Context::getContext($coroutineName)) instanceof PDO) {
			return;
		}
		if ($client->inTransaction()) {
			$client->commit();
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
		$this->getPool()->clean($this->getPool()->name($name, $isMaster));
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
			if (!$result) {
				$this->getPool()->decrement($name);
			}
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
		$coroutineName = $this->getPool()->name('Mysql:' . $coroutineName, $isMaster);
		$this->getPool()->clean($coroutineName);
	}


	/**
	 * @return ClientsPool
	 * @throws Exception
	 */
	public function getPool(): ClientsPool
	{
		if (!$this->clientsPool) {
			$this->clientsPool = Snowflake::app()->getClientsPool();
		}
		return $this->clientsPool;
	}

}
