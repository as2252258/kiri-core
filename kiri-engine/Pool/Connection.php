<?php
declare(strict_types=1);

namespace Kiri\Pool;

use Exception;
use Kiri;
use Kiri\Abstracts\Component;
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
		$connection = Context::get($coroutineName);
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
		$connection = Context::get($coroutineName);
		if ($connection instanceof PDO) {
			$connection->rollBack();
		}
	}


	/**
	 * @param string $cds
	 * @return PDO|bool|null
	 * @throws Exception
	 */
	public function get(string $cds): null|PDO|bool
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
	 * @param string $name
	 * @param PDO $PDO
	 * @return void
	 * @throws ConfigException
	 */
	public function addItem(string $name, PDO $PDO): void
	{
		$this->pool->push($name, $PDO);
	}


	/**
	 * @param array $config
	 * @param int $max
	 */
	public function initConnections(array $config, int $max)
	{
		$this->pool->initConnections($config['cds'], $max, static function () use ($config) {
			$options = [
				PDO::ATTR_CASE               => PDO::CASE_NATURAL,
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
				PDO::ATTR_STRINGIFY_FETCHES  => false,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_TIMEOUT            => $config['connect_timeout'],
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . ($config['charset'] ?? 'utf8mb4')
			];
			if (!Context::inCoroutine()) {
				$options[PDO::ATTR_PERSISTENT] = true;
			}
			$link = new PDO('mysql:dbname=' . $config['dbname'] . ';host=' . $config['cds'],
				$config['username'], $config['password'], $options);
			foreach ($config['attributes'] as $key => $attribute) {
				$link->setAttribute($key, $attribute);
			}
			return $link;
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
