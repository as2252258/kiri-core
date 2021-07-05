<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:09
 */
declare(strict_types=1);


namespace Database;


use Database\Mysql\Schema;
use Exception;
use JetBrains\PhpStorm\Pure;
use PDO;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
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

	public string $database = '';

	public int $timeout = 1900;

	public int $maxNumber = 30;
	public int $minNumber = 10;

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
		Event::on(Event::SYSTEM_RESOURCE_CLEAN, [$this, 'disconnect']);
		Event::on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'clear_connection']);
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

		Event::on(Connection::TRANSACTION_COMMIT, [$this, 'commit'], false, true);
		Event::on(Connection::TRANSACTION_ROLLBACK, [$this, 'rollback'], false, true);
	}

	/**
	 * @param null $sql
	 * @return PDO
	 * @throws Exception
	 */
	public function getConnect($sql = NULL): PDO
	{
		return $this->getPdo($sql);
	}


	/**
	 * @throws Exception
	 */
	public function fill()
	{
		$connections = $this->connections();
		$pool = Config::get('databases.pool.max', 10);

		$connections->initConnections('Mysql:' . $this->cds, true, $pool);
		if (!empty($this->slaveConfig) && $this->cds != $this->slaveConfig['cds']) {
			$connections->initConnections('Mysql:' . $this->slaveConfig['cds'], false, $pool);
		}
	}


	/**
	 * @param $sql
	 * @return PDO
	 * @throws Exception
	 */
	private function getPdo($sql): PDO
	{
		if ($this->isWrite($sql)) {
			return $this->masterInstance();
		} else {
			return $this->slaveInstance();
		}
	}

	/**
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function getSchema(): Schema
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
		if (str_starts_with(strtolower($sql), 'select')) {
			return false;
		}
		return true;
	}

	/**
	 * @return mixed
	 * @throws Exception
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
		return $this->connections()->get([
			'cds'      => $this->cds,
			'username' => $this->username,
			'password' => $this->password,
			'database' => $this->database
		], true);
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function slaveInstance(): PDO
	{
		if (empty($this->slaveConfig) || Db::transactionsActive()) {
			return $this->masterInstance();
		}
		if ($this->slaveConfig['cds'] == $this->cds) {
			return $this->masterInstance();
		}
		return $this->connections()->get($this->slaveConfig, false);
	}


	/**
	 * @return \Snowflake\Pool\Connection
	 * @throws Exception
	 */
	private function connections(): \Snowflake\Pool\Connection
	{
		return Snowflake::app()->getMysqlFromPool();
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
	 * @param null $sql
	 * @param string $dbname
	 * @param array $attributes
	 * @return Command
	 * @throws Exception
	 */
	public function createCommand($sql = null, string $dbname = '', array $attributes = []): Command
	{
		if (!empty($dbname)) {
			$sql = $this->clear($dbname, $sql);
		}
		$command = new Command(['db' => $this, 'sql' => $this->innerJoinMatch($dbname, $sql)]);
		return $command->bindValues($attributes);
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function clear($dbname, $sql): array|string|null
	{
		$substr = strtoupper(substr($sql, 0, 6));
		return match ($substr) {
			'SELECT', 'SHOW F', 'DELETE' => $this->selectMatch($dbname, $sql),
			'UPDATE' => $this->updateMatch($dbname, $sql),
			'INSERT' => $this->insertMatch($dbname, $sql),
			'TRUNCA' => $this->truncateMatch($dbname, $sql),
			default => $sql
		};
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function selectMatch($dbname, $sql): array|string|null
	{
		return preg_replace('/FROM\s+(\w+)\s+/', 'FROM `' . $dbname . '`.$1 ', $sql);
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function innerJoinMatch($dbname, $sql): array|string|null
	{
		return preg_replace('/(INNER|LEFT|RIGHT) JOIN\s+(\w+)\s/', '$1 JOIN `' . $dbname . '`.$2 ', $sql);
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function updateMatch($dbname, $sql): array|string|null
	{
		return preg_replace('/UPDATE\s+(\w+)\s+SET/', 'UPDATE `' . $dbname . '`.$1 SET', $sql);
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function insertMatch($dbname, $sql): array|string|null
	{
		return preg_replace('/INSERT INTO\s+(\w+)/', 'INSERT INTO `' . $dbname . '`.$1', $sql);
	}


	/**
	 * @param $dbname
	 * @param $sql
	 * @return array|string|null
	 */
	private function truncateMatch($dbname, $sql): array|string|null
	{
		return preg_replace('/TRUNCATE\s+(\w+)\s+/', 'TRUNCATE `' . $dbname . '`.$1', $sql);
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
	 * @throws Exception
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
		$connections->disconnect($this->cds, true);
		$connections->disconnect($this->slaveConfig['cds'], false);
	}

}
