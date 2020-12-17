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


	private int $creates = 0;


	public int $lastTime = 0;

	/**
	 * @param $timer
	 */
	public function Heartbeat_detection($timer)
	{
		$this->creates = $timer;
		if ($this->lastTime == 0) {
			return;
		}
		if ($this->lastTime + 600 < time()) {
			$this->flush(0);
		} else if ($this->lastTime + 300 < time()) {
			$this->flush(2);
		}
	}


	/**
	 * @param $retain_number
	 */
	protected function flush($retain_number)
	{
		$channels = $this->getChannels();
		foreach ($channels as $name => $channel) {
			$this->pop($channel, $name, $retain_number);
		}
		if ($retain_number == 0) {
			$this->debug('release Timer::tick');
			Timer::clear($this->creates);
			$this->creates = 0;
		}
	}


	/**
	 * @param $channel
	 * @param $name
	 * @param $retain_number
	 */
	protected function pop($channel, $name, $retain_number)
	{
		while ($channel->length() > $retain_number) {
			[$timer, $connection] = $channel->pop();
			if ($connection) {
				unset($connection);
			}
			$this->desc($name);
		}
	}


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
		$coroutineName = $this->name($cds, true);
		if (!Context::hasContext('begin_' . $coroutineName)) {
			return false;
		}
		return Context::getContext('begin_' . $coroutineName) == 0;
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
			$this->info('connection commit.');
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
		if ($this->hasClient($coroutineName)) {
			/** @var PDO $connection */
			$connection = Context::getContext($coroutineName);
			if ($connection->inTransaction()) {
				$this->info('connection rollBack.');
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
		if (Context::hasContext($coroutineName)) {
			return Context::getContext($coroutineName);
		}
		if ($this->size($coroutineName) < 1 && $this->hasCreate[$coroutineName] < $this->max) {
			return $this->saveClient($coroutineName, $this->nowClient($coroutineName, $config));
		}
		$connections = $client = $this->get($coroutineName);
		if ($connections[1] instanceof PDO) {
			return $this->saveClient($coroutineName, $connections[1]);
		}
		return $this->saveClient($coroutineName, $this->nowClient($coroutineName, $config));
	}


	/**
	 * @param $coroutineName
	 * @param $client
	 * @return mixed
	 */
	private function saveClient($coroutineName, $client): mixed
	{
		return Context::setContext($coroutineName, $client);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return PDO
	 * @throws Exception
	 */
	private function nowClient($coroutineName, $config): PDO
	{
		$client = $this->createConnect($coroutineName, ...$this->parseConfig($config));
		if ($number = Context::getContext('begin_' . $coroutineName, Coroutine::getCid())) {
			$number > 0 && $client->beginTransaction();
		}
		if ($this->creates === 0) {
			$this->creates = Timer::tick(10000, [$this, 'Heartbeat_detection']);
		}
		return $client;
	}


	/**
	 * @param $config
	 * @return array
	 */
	private function parseConfig($config): array
	{
		return [$config['cds'], $config['username'], $config['password'], $config['charset'] ?? 'utf8mb4'];
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
			if ($time + 60 * 10 > time()) {
				return $result = true;
			}
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
	 * @param $cds
	 * @param $username
	 * @param $password
	 * @param string $charset
	 * @return PDO
	 * @throws Exception
	 */
	public function createConnect($coroutineName, $cds, $username, $password, $charset = 'utf8mb4'): PDO
	{
		try {
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
			$this->success('create db client -> ' . $cds . ':' . $this->hasCreate[$coroutineName] . ':' . $this->size($coroutineName));
			$this->incr($coroutineName);
			return $link;
		} catch (\Throwable $exception) {
			$this->addError($cds . '  ->  ' . $exception->getMessage());
			if ($exception->getCode() === 2006) {
				return $this->createConnect($coroutineName, $cds, $username, $password, $charset);
			}
			throw new Exception($exception->getMessage());
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
	 * @param string $coroutineName
	 */
	public function desc(string $coroutineName)
	{
		if (!isset($this->hasCreate[$coroutineName])) {
			$this->hasCreate[$coroutineName] = 0;
		}
		$this->hasCreate[$coroutineName] -= 1;
	}
}
