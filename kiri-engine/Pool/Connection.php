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
	 * @param bool $isMaster
	 * @return PDO|null
	 * @throws Kiri\Exception\ConfigException
	 */
	public function get(mixed $config, bool $isMaster = false): ?\PDO
	{
		$name = $config['cds'] . ($isMaster ? 'master' : 'slave');
		if (!$this->pool->hasChannel($name)) {
			$this->pool->initConnections($name, $config['pool']['max']);
		}
		if (!Context::hasContext($name)) {
			return Context::setContext($name, $this->pool->get($name, $this->generate($config)));
		} else {
			return Context::getContext($name);
		}
	}


	/**
	 * @param array $config
	 * @return Closure
	 */
	public function generate(array $config): Closure
	{
		return static function () use ($config) {
			Kiri::getDi()->get(Kiri\Error\StdoutLoggerInterface::class)->alert('create database connect(' . $config['cds'] . ')');

			$link = new \PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['cds'], $config['username'], $config['password'], [
				\PDO::ATTR_EMULATE_PREPARES   => false,
				\PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
				\PDO::ATTR_PERSISTENT         => true,
				\PDO::ATTR_TIMEOUT            => $config['connect_timeout'],
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . ($config['charset'] ?? 'utf8mb4')
			]);
			$link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$link->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
			$link->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_EMPTY_STRING);
			foreach ($config['attributes'] as $key => $attribute) {
				$link->setAttribute($key, $attribute);
			}
			if (Db::inTransactionsActive()) {
				$link->beginTransaction();
			}
			return $link;
		};
	}


	/**
	 * @param string $name
	 * @return array
	 * @throws Kiri\Exception\ConfigException
	 */
	public function check(string $name): array
	{
		return $this->pool->check($name);
	}


	/**
	 * @param $coroutineName
	 * @param $config
	 * @return Closure
	 */
	public function create($coroutineName, $config): Closure
	{
		return static function () use ($coroutineName, $config) {
			return new PDO($config);
		};
	}


	/**
	 * @param string $name
	 * @param PDO $PDO
	 * @return void
	 * @throws Kiri\Exception\ConfigException
	 * @throws Exception
	 */
	public function addItem(string $name, PDO $PDO): void
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
