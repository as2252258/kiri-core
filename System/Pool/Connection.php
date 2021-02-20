<?php
declare(strict_types=1);

namespace Snowflake\Pool;

use HttpServer\Http\Context;
use PDO;
use Exception;
use Snowflake\Abstracts\Config;
use Swoole\Coroutine;
use Snowflake\Abstracts\Pool;
use Swoole\Timer;

/**
 * Class Connection
 * @package Snowflake\Pool
 */
class Connection extends Pool
{


	public array $hasCreate = [];

	public int $timeout = 1900;

	/** @var PDO[] */
	protected array $connections = [];


	/**
	 * @param $timeout
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}


	/**
	 * @param $value
	 */
	public function setLength($value)
	{
		$this->max = $value;
	}

	/**
	 * @param $cds
	 * @return bool
	 *
	 * db is in transaction
	 */
	public function inTransaction($cds): bool
	{
		return Context::getContext('begin_' . $this->name($cds, true)) == 0;
	}

	/**
	 * @param $coroutineName
	 */
	public function beginTransaction($coroutineName)
	{
		$coroutineName = $this->name($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			Context::setContext('begin_' . $coroutineName, 0);
		}
		Context::autoIncr('begin_' . $coroutineName);
		if (!Context::getContext('begin_' . $coroutineName) !== 0) {
			return;
		}
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO && !$connection->inTransaction()) {
			$connection->beginTransaction();
		}
	}

	/**
	 * @param $coroutineName
	 */
	public function commit($coroutineName)
	{
		$coroutineName = $this->name($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			return;
		}
		if (Context::autoDecr('begin_' . $coroutineName) > 0) {
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
	 * @param $name
	 * @param false $isMaster
	 * @return array
	 */
	private function getIndex($name, $isMaster = false): array
	{
		return [Coroutine::getCid(), $this->name($name, $isMaster)];
	}

	/**
	 * @param $coroutineName
	 */
	public function rollback($coroutineName)
	{
		$coroutineName = $this->name($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			return;
		}
		if (Context::autoDecr('begin_' . $coroutineName) > 0) {
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
	 * @param array $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster = false): mixed
	{
		$coroutineName = $this->name($config['cds'], $isMaster);
		if (!isset($this->hasCreate[$coroutineName])) {
			$this->hasCreate[$coroutineName] = 0;
		}
		if (($pdo = Context::getContext($coroutineName)) instanceof PDO) {
			return $pdo;
		}
		if (!$this->hasItem($coroutineName)) {
			$this->newClient($config, $coroutineName);
		}
		[$time, $connections] = $this->get($coroutineName);
		if (!($connections instanceof PDO)) {
			return $this->getConnection($config, $coroutineName);
		}
		return Context::setContext($coroutineName, $connections);
	}


	/**
	 * @param $config
	 * @param $coroutineName
	 * @throws Exception
	 */
	private function newClient($config, $coroutineName)
	{
		$this->printClients($config['cds'], $coroutineName, true);
		$this->createConnect($this->parseConfig($config, $coroutineName), $coroutineName, function ($cds, $username, $password, $charset, $coroutineName) {
			$link = new PDO($cds, $username, $password, [
				PDO::ATTR_EMULATE_PREPARES => false,
				PDO::ATTR_CASE             => PDO::CASE_NATURAL,
				PDO::ATTR_TIMEOUT          => $this->timeout,
			]);
			$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
			$link->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
			if (!empty($charset)) {
				$link->query('SET NAMES ' . $charset);
			}
			$this->incr($coroutineName);
			if ($number = Context::getContext('begin_' . $coroutineName, Coroutine::getCid())) {
				$number > 0 && $link->beginTransaction();
			}
			return $link;
		});
	}


	/**
	 * @param $config
	 * @param $name
	 * @return array
	 */
	private function parseConfig($config, $name): array
	{
		return [$config['cds'], $config['username'], $config['password'], $config['charset'] ?? 'utf8mb4', $name];
	}


	/**
	 * @param $cds
	 * @param $coroutineName
	 * @param false $isBefore
	 */
	public function printClients($cds, $coroutineName, $isBefore = false)
	{
		$this->warning(($isBefore ? 'before ' : '') . 'create client[address: ' . $cds . ', ' . env('workerId') . ', coroutine: ' . Coroutine::getCid() . ', has num: ' . $this->size($coroutineName) . ', has create: ' . $this->hasCreate[$coroutineName] . ']');
	}


	/**
	 * @param $coroutineName
	 * @param $isMaster
	 */
	public function release($coroutineName, $isMaster)
	{
		$coroutineName = $this->name($coroutineName, $isMaster);
		if (!$this->hasClient($coroutineName)) {
			return;
		}

		/** @var PDO $client */
		$client = Context::getContext($coroutineName);
		if ($client->inTransaction()) {
			$client->commit();
		}
		$this->push($coroutineName, $client);
		$this->remove($coroutineName);
		$this->lastTime = time();
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
	 */
	public function connection_clear()
	{
		$connections = Context::getAllContext();
		foreach ($connections as $name => $connection) {
			if (empty($connection) || !($connection instanceof PDO)) {
				continue;
			}
			/** @var PDO $pdoClient */
			if ($connection->inTransaction()) {
				$connection->commit();
			}
			$this->push($name, $connection);
			$this->remove($name);
		}
		$this->hasCreate = [];
		$this->creates = 0;
	}


	/**
	 * @param $coroutineName
	 */
	public function remove($coroutineName)
	{
		Context::deleteId($coroutineName);
	}

	/**
	 * @param $name
	 * @param $time
	 * @param $client
	 * @return bool
	 */
	public function checkCanUse($name, $time, $client): bool
	{
		try {
			if (empty($client) || !($client instanceof PDO)) {
				return $result = false;
			}
			if (!$client->getAttribute(PDO::ATTR_SERVER_INFO)) {
				return $result = false;
			}
			return $result = true;
		} catch (\Swoole\Error | \Throwable $exception) {
			return $result = false;
		} finally {
			if (!$result) {
				$this->desc($name);
			}
		}
	}


	/**
	 * @param $coroutineName
	 * @throws Exception
	 */
	public function disconnect($coroutineName)
	{
		if (!$this->hasClient($coroutineName)) {
			return;
		}
		$this->remove($coroutineName);
		$this->clean($coroutineName);
	}

	/**
	 * @param $coroutineName
	 */
	public function incr($coroutineName)
	{
		if (!isset($this->hasCreate[$coroutineName])) {
			$this->hasCreate[$coroutineName] = 0;
		}
		$this->hasCreate[$coroutineName] += 1;
	}

	/**
	 * @param string $name
	 */
	public function desc(string $name)
	{
		if (!isset($this->hasCreate[$name])) {
			$this->hasCreate[$name] = 0;
		}
		$this->hasCreate[$name] -= 1;
	}
}
