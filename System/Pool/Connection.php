<?php

namespace Snowflake\Pool;

use HttpServer\Http\Context;
use PDO;
use Exception;
use Swoole\Coroutine;
use Snowflake\Abstracts\Pool;

/**
 * Class Connection
 * @package Snowflake\Pool
 */
class Connection extends Pool
{

	public $hasCreate = [];

	public $timeout = 1900;

	/** @var PDO[] */
	protected $connections = [];


	private $creates = 0;

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
	public function inTransaction($cds)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($cds, true);
		if (!Context::hasContext('begin_' . $coroutineName, $coroutineId)) {
			return false;
		}
		return Context::getContext('begin_' . $coroutineName, $coroutineId) == 0;
	}

	/**
	 * @param $coroutineName
	 */
	public function beginTransaction($coroutineName)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName, $coroutineId)) {
			Context::setContext('begin_' . $coroutineName, 0, $coroutineId);
		}
		if (Context::getContext('begin_' . $coroutineName, $coroutineId) === 0) {
			$connection = Context::getContext($coroutineName);
			if ($connection instanceof PDO && !$connection->inTransaction()) {
				$connection->beginTransaction();
			}
		}
		Context::autoIncr('begin_' . $coroutineName, $coroutineId);
	}

	/**
	 * @param $coroutineName
	 */
	public function commit($coroutineName)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName, $coroutineId)) {
			return;
		}
		if (Context::autoDecr('begin_' . $coroutineName, $coroutineId) > 0) {
			return;
		}
		$connection = Context::getContext($coroutineName);
		if ($connection instanceof PDO) {
			if ($connection->inTransaction()) {
				$this->info('connection commit.');
				$connection->commit();
			}
			Context::setContext('begin_' . $coroutineName, 0, $coroutineId);
		}
	}

	/**
	 * @param $coroutineId
	 * @param $coroutineName
	 * @return array
	 */
	private function instanceTrance($coroutineId, $coroutineName)
	{
		return [$coroutineId, $coroutineName];
	}

	/**
	 * @param $name
	 * @param false $isMaster
	 * @return array
	 */
	private function getIndex($name, $isMaster = false)
	{
		return $this->instanceTrance(Coroutine::getCid(), $this->name($name, $isMaster));
	}

	/**
	 * @param $coroutineName
	 */
	public function rollback($coroutineName)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($coroutineName, true);
		if (!Context::hasContext('begin_' . $coroutineName, $coroutineId)) {
			return;
		}
		if (Context::autoDecr('begin_' . $coroutineName, $coroutineId) > 0) {
			return;
		}
		if ($this->hasClient($coroutineName)) {
			/** @var PDO $connection */
			$connection = Context::getContext($coroutineName);
			if ($connection->inTransaction()) {
				$this->info('connection rollBack.');
				$connection->rollBack();
			}
		}
		Context::setContext('begin_' . $coroutineName, 0, $coroutineId);
	}


	/**
	 * @param array $config
	 * @param bool $isMaster
	 * @return mixed
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster = false)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($config['cds'], $isMaster);
		if (!isset($this->hasCreate[$coroutineName])) {
			$this->hasCreate[$coroutineName] = 0;
		}
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		}
		if ($this->size($coroutineName) < 1 && $this->hasCreate[$coroutineName] < $this->max) {
			$this->info('client has create :' . ($this->hasCreate[$coroutineName] ?? 0) . ':' . $this->max);
			return $this->saveClient($coroutineName, $this->nowClient($coroutineName, $config));
		}
		return $this->getByChannel($coroutineName, $config);
	}


	/**
	 * @param $name
	 * @param $config
	 * @return Connection
	 * @throws Exception
	 */
	public function fill($name, $config)
	{
		if ($this->size($name) >= 10) {
			return $this;
		}
		for ($i = 0; $i < 10 - $this->size($name); $i++) {
			$this->push($name, $this->createConnect($config['cds'], $config['username'], $config['password']));
			$this->incr($name);
		}
		return $this;
	}

	/**
	 * @param $coroutineName
	 * @param $config
	 * @return mixed
	 * @throws Exception
	 */
	public function getByChannel($coroutineName, $config)
	{
		[$time, $client] = $this->get($coroutineName);
		if ($client instanceof PDO) {
			return $this->saveClient($coroutineName, $client);
		}
		return $this->getByChannel($coroutineName, $config);
	}


	/**
	 * @param $coroutineName
	 * @param $client
	 * @return mixed
	 */
	private function saveClient($coroutineName, $client)
	{
		return Context::setContext($coroutineName, $client);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return PDO
	 * @throws Exception
	 */
	private function nowClient($coroutineName, $config)
	{
		$this->success('create db client -> ' . $config['cds'] . ':' . $this->hasCreate[$coroutineName] . ':' . $this->size($coroutineName));
		$client = $this->createConnect($config['cds'], $config['username'], $config['password']);
		if (isset(Context::getContext('begin_' . $coroutineName)[Coroutine::getCid()])) {
			$number = Context::getContext('begin_' . $coroutineName)[Coroutine::getCid()];
			$number > 0 && $client->beginTransaction();
		}
		$this->incr($coroutineName);
		return $client;
	}


	/**
	 * @param $coroutineName
	 * @param $isMaster
	 */
	public function release($coroutineName, $isMaster)
	{
		[$coroutineId, $coroutineName] = $this->getIndex($coroutineName, $isMaster);
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
	}


	/**
	 * @param $coroutineName
	 * @return bool
	 */
	private function hasClient($coroutineName)
	{
		return Context::hasContext($coroutineName);
	}


	/**
	 * batch release
	 */
	public function connection_clear()
	{
		$this->debug('receive all clients.');
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
	 * @param $connect
	 * @return bool
	 */
	public function checkCanUse($name, $time, $connect)
	{
		try {
			if ($time + 60 * 10 < time()) {
				return $result = false;
			}
			if (empty($connect) || !($connect instanceof PDO)) {
				return $result = false;
			}
			if (!$connect->getAttribute(PDO::ATTR_SERVER_INFO)) {
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
	 * @param $cds
	 * @param $username
	 * @param $password
	 * @return PDO
	 * @throws Exception
	 */
	public function createConnect($cds, $username, $password)
	{
		try {
			$link = new PDO($cds, $username, $password, [
				PDO::ATTR_EMULATE_PREPARES => false,
				//                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
				PDO::ATTR_CASE             => PDO::CASE_NATURAL,
				PDO::ATTR_TIMEOUT          => $this->timeout,
			]);
			$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
			$link->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
			return $link;
		} catch (\Throwable $exception) {
			if ($exception->getCode() !== 2006) {
				$this->addError($cds . '  ->  ' . $exception->getMessage());
				throw new Exception($exception->getMessage());
			}
			$this->addError($cds . '  ->  ' . $exception->getMessage());
			return $this->createConnect($cds, $username, $password);
		}
	}

	/**
	 * @param $coroutineName
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
	 * @param $coroutineName
	 */
	public function desc($coroutineName)
	{
		if (!isset($this->hasCreate[$coroutineName])) {
			$this->hasCreate[$coroutineName] = 0;
		}
		$this->hasCreate[$coroutineName] -= 1;
	}
}
