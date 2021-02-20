<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:09
 */
declare(strict_types=1);


namespace Database;


use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Database\Mysql\Schema;
use Database\Orm\Select;
use Exception;
use PDO;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Connection
 * @package Database
 */
class Connection extends Component
{
	const TRANSACTION_COMMIT = 'transaction::commit';
	const TRANSACTION_ROLLBACK = 'transaction::rollback';

	public string $id = 'db';
	public string $cds = '';
	public string $password = '';
	public string $username = '';
	public string $charset = 'utf-8';
	public string $tablePrefix = '';

	public int $timeout = 1900;

	public int $maxNumber = 200;

	/**
	 * @var bool
	 * enable database cache
	 */
	public bool $enableCache = false;
	public string $cacheDriver = 'redis';

	/**
	 * @var array
	 *
	 * @example [
	 *    'cds'      => 'mysql:dbname=dbname;host=127.0.0.1',
	 *    'username' => 'root',
	 *    'password' => 'root'
	 * ]
	 */
	public array $slaveConfig = [];

	private ?Schema $_schema = null;


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$event = Snowflake::app()->getEvent();
		$event->on(Event::SYSTEM_RESOURCE_CLEAN, [$this, 'disconnect']);
		$event->on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'clear_connection']);
	}


	/**
	 * @throws Exception
	 */
	public function enablingTransactions()
	{
		if (!Db::transactionsActive()) {
			return;
		}
		$this->beginTransaction();

		$event = Snowflake::app()->getEvent();
		$event->on(Connection::TRANSACTION_COMMIT, [$this, 'commit'], false, true);
		$event->on(Connection::TRANSACTION_ROLLBACK, [$this, 'rollback'], false, true);
	}

	/**
	 * @param null $sql
	 * @return PDO
	 * @throws Exception
	 */
	public function getConnect($sql = NULL): PDO
	{
		$connections = $this->connections();
		$connections->initConnections('mysql', $this->cds, true, $this->maxNumber);
		$connections->initConnections('mysql', $this->slaveConfig['cds'], false, $this->maxNumber);
		$connections->setTimeout($this->timeout);

		return $this->getPdo($sql);
	}


	/**
	 * 初始化 Channel
	 * @throws ComponentException
	 */
	public function fill()
	{
		$connections = $this->connections();
		$connections->initConnections('mysql', $this->cds, true, $this->maxNumber);
		$connections->initConnections('mysql', $this->slaveConfig['cds'], false, $this->maxNumber);
	}


	/**
	 * @param $sql
	 * @return PDO
	 * @throws Exception
	 */
	private function getPdo($sql): PDO
	{
		if ($this->isWrite($sql)) {
			$connect = $this->masterInstance();
		} else {
			$connect = $this->slaveInstance();
		}
		return $connect;
	}

	/**
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function getSchema(): mixed
	{
		if ($this->_schema === null) {
			$this->_schema = Snowflake::createObject([
				'class' => Schema::class,
				'db'    => $this
			]);
		}
		return $this->_schema;
	}

	/**
	 * @param $sql
	 * @return bool
	 */
	#[Pure] public function isWrite($sql): bool
	{
		if (empty($sql)) return false;

		$prefix = strtolower(mb_substr($sql, 0, 6));

		return in_array($prefix, ['insert', 'update', 'delete']);
	}

	/**
	 * @return mixed
	 * @throws ComponentException
	 */
	public function getCacheDriver(): mixed
	{
		if (!$this->enableCache) {
			return null;
		}
		return Snowflake::app()->get($this->cacheDriver);
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function masterInstance(): PDO
	{
		return $this->connections()->getConnection([
			'cds'      => $this->cds,
			'username' => $this->username,
			'password' => $this->password
		], true);
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function slaveInstance(): PDO
	{
		if (empty($this->slaveConfig) || $this->slaveConfig['cds'] == $this->cds) {
			return $this->masterInstance();
		}
		return $this->connections()->getConnection($this->slaveConfig, false);
	}


	/**
	 * @return \Snowflake\Pool\Connection
	 * @throws ComponentException
	 */
	private function connections(): \Snowflake\Pool\Connection
	{
		return Snowflake::app()->getConnections();
	}


	/**
	 * @return $this
	 * @throws Exception
	 */
	public function beginTransaction(): static
	{
		$this->connections()->beginTransaction($this->cds);
		return $this;
	}

	/**
	 * @return $this|bool
	 * @throws Exception
	 */
	public function inTransaction(): bool|static
	{
		return $this->connections()->inTransaction($this->cds);
	}

	/**
	 * @throws Exception
	 * 事务回滚
	 */
	public function rollback()
	{
		$this->connections()->rollback($this->cds);
	}

	/**
	 * @throws Exception
	 * 事务提交
	 */
	public function commit()
	{
		$this->connections()->commit($this->cds);
	}

	/**
	 * @param $sql
	 * @return PDO
	 * @throws Exception
	 */
	public function refresh($sql): PDO
	{
		if ($this->isWrite($sql)) {
			$instance = $this->masterInstance();
		} else {
			$instance = $this->slaveInstance();
		}
		return $instance;
	}

	/**
	 * @param $sql
	 * @param array $attributes
	 * @return Command
	 * @throws
	 */
	public function createCommand($sql = null, $attributes = []): Command
	{
		$command = new Command(['db' => $this, 'sql' => $sql]);
		return $command->bindValues($attributes);
	}

	/**
	 * @return Select
	 * @throws Exception
	 */
	public function getBuild(): Select
	{
		return $this->getSchema()->getQueryBuilder();
	}

	/**
	 *
	 * 回收链接
	 * @throws
	 */
	public function release()
	{
		$connections = $this->connections();

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}


	/**
	 * 临时回收
	 * @throws ComponentException
	 */
	public function recovery()
	{
		$connections = $this->connections();

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}

	/**
	 *
	 * 回收链接
	 * @throws
	 */
	public function clear_connection()
	{
		$connections = $this->connections();

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}


	/**
	 * @throws Exception
	 */
	public function disconnect()
	{
		$connections = $this->connections();
		$connections->disconnect($this->cds);
		$connections->disconnect($this->slaveConfig['cds']);
	}

}
