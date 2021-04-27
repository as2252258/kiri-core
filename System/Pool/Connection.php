<?php
declare(strict_types=1);

namespace Snowflake\Pool;

use Exception;
use HttpServer\Http\Context;
use PDO;
use Snowflake\Abstracts\Pool;
use Swoole\Coroutine;
use Swoole\Error;
use Throwable;

/**
 * Class Connection
 * @package Snowflake\Pool
 */
class Connection extends Pool
{


	public int $timeout = 1900;

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
		return Context::getContext('begin_' . $this->name('mysql', $cds, true)) == 0;
	}

	/**
	 * @param $coroutineName
	 */
	public function beginTransaction($coroutineName)
	{
		$coroutineName = $this->name('mysql', $coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			Context::setContext('begin_' . $coroutineName, 0);
		}
		Context::increment('begin_' . $coroutineName);
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
		$coroutineName = $this->name('mysql', $coroutineName, true);
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
	 * @param $name
	 * @param false $isMaster
	 * @return array
	 */
	private function getIndex($name, $isMaster = false): array
	{
		return [Coroutine::getCid(), $this->name('mysql', $name, $isMaster)];
	}

	/**
	 * @param $coroutineName
	 */
	public function rollback($coroutineName)
	{
		$coroutineName = $this->name('mysql', $coroutineName, true);
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
	public function get(mixed $config, $isMaster = false): mixed
	{
		$coroutineName = $this->name('mysql', $config['cds'], $isMaster);
		if (($pdo = Context::getContext($coroutineName)) instanceof PDO) {
			return $pdo;
		}
		$connections = $this->getFromChannel($coroutineName, $config);
		if ($number = Context::getContext('begin_' . $coroutineName, Coroutine::getCid())) {
			$number > 0 && $connections->beginTransaction();
		}
		return Context::setContext($coroutineName, $connections);
	}


	/**
	 * @param string $name
	 * @param mixed $config
	 * @return PDO
	 * @throws Exception
	 */
	public function createClient(string $name, mixed $config): PDO
	{
		$link = new PDO($config['cds'], $config['username'], $config['password'], [
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_CASE             => PDO::CASE_NATURAL,
			PDO::ATTR_TIMEOUT          => $this->timeout,
		]);
		$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
		$link->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);

		$charset = $config['charset'] ?? 'utf8mb4';
		if (!empty($charset)) {
			$link->query('SET NAMES ' . $charset);
		}

//		$this->printClients($config['cds'], '', '');
//		$this->increment($name);

		return $link;
	}


	/**
	 * @param $cds
	 * @param $coroutineName
	 * @param false $isBefore
	 * @throws Exception
	 */
	public function printClients($cds, $coroutineName, $isBefore = false)
	{
//		$this->success('create client connect ' . $cds);
	}


	/**
	 * @param $coroutineName
	 * @param $isMaster
	 * @throws Exception
	 */
	public function release($coroutineName, $isMaster)
	{
		$coroutineName = $this->name('mysql', $coroutineName, $isMaster);
		if (!$this->hasClient($coroutineName)) {
			return;
		}

//		$this->error('Worker ' . env('worker') . ' recover db client ' . $coroutineName . ' length ' . $this->size($coroutineName));

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
	 * @throws Exception
	 */
	public function connection_clear()
	{
		$this->flush(0);
	}


	/**
	 * @param $coroutineName
	 */
	public function remove($coroutineName)
	{
		Context::remove($coroutineName);
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
				$this->decrement($name);
			}
			return $result;
		}
	}


	/**
	 * @param $coroutineName
	 * @param bool $isMaster
	 * @throws Exception
	 */
	public function disconnect($coroutineName, $isMaster = false)
	{
		$coroutineName = $this->name($coroutineName, $isMaster);
		if ($this->hasClient($coroutineName)) {
			$this->remove($coroutineName);
		}
		$this->clean($coroutineName);
	}

}
