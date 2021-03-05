<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 15:23
 */
declare(strict_types=1);

namespace Database;


use ReflectionException;
use Snowflake\Abstracts\Component;
use Exception;
use PDO;
use PDOStatement;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;

/**
 * Class Command
 * @package Database
 */
class Command extends Component
{
	const ROW_COUNT = 'ROW_COUNT';
	const FETCH = 'FETCH';
	const FETCH_ALL = 'FETCH_ALL';
	const EXECUTE = 'EXECUTE';
	const FETCH_COLUMN = 'FETCH_COLUMN';

	/** @var Connection */
	public Connection $db;

	/** @var ?string */
	public ?string $sql = '';

	/** @var array */
	public array $params = [];

	/** @var string */
	private string $_modelName;

	private ?PDOStatement $prepare = null;


	/**
	 * @return array|bool|int|string|PDOStatement|null
	 * @throws Exception
	 */
	public function incrOrDecr(): array|bool|int|string|PDOStatement|null
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param bool $isInsert
	 * @param bool $hasAutoIncrement
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function save($isInsert = TRUE, $hasAutoIncrement = null): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE, $isInsert, $hasAutoIncrement);
	}


	/**
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function all(): int|bool|array|string|null
	{
		return $this->execute(static::FETCH_ALL);
	}

	/**
	 * @return array|bool|int|string|null
	 * @throws Exception
	 */
	public function one(): null|array|bool|int|string
	{
		return $this->execute(static::FETCH);
	}

	/**
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function fetchColumn(): int|bool|array|string|null
	{
		return $this->execute(static::FETCH_COLUMN);
	}

	/**
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function rowCount(): int|bool|array|string|null
	{
		return $this->execute(static::ROW_COUNT);
	}

	/**
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function flush(): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param $type
	 * @param null $isInsert
	 * @param bool $hasAutoIncrement
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	private function execute($type, $isInsert = null, $hasAutoIncrement = null): int|bool|array|string|null
	{
		try {
			$time = microtime(true);
			if ($type === static::EXECUTE) {
				$result = $this->insert_or_change($isInsert, $hasAutoIncrement);
			} else {
				$result = $this->search($type);
			}
			$this->setExecuteLog($time);
			if ($this->prepare) {
				$this->prepare->closeCursor();
			}
		} catch (\Throwable $exception) {
			$result = $this->addError($this->sql . '. error: ' . $exception->getMessage(), 'mysql');
		} finally {
			return $result;
		}
	}


	/**
	 * @param $time
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function setExecuteLog($time)
	{
		$export['sql'] = $this->sql;
		$export['param'] = $this->params;
		$export['time'] = microtime(true) - $time;

		logger()->debug(var_export($export, true), 'mysql');
	}


	/**
	 * @param $type
	 * @return mixed
	 * @throws Exception
	 */
	private function search($type): mixed
	{
		$connect = $this->db->getConnect($this->sql);
		if (!($connect instanceof PDO)) {
			return $this->addError('数据库繁忙, 请稍后再试.');
		}
		if (!($query = $connect->query($this->sql))) {
			return $this->addError($connect->errorInfo()[2] ?? '数据库异常, 请稍后再试.');
		}
		return match ($type) {
			self::ROW_COUNT => $query->rowCount(),
			self::FETCH_COLUMN => $query->fetchColumn(),
			self::FETCH_ALL => $query->fetchAll(PDO::FETCH_ASSOC),
			default => $query->fetch(PDO::FETCH_ASSOC)
		};
	}

	/**
	 * @param $isInsert
	 * @param $hasAutoIncrement
	 * @return bool|string
	 * @throws Exception
	 */
	private function insert_or_change($isInsert, $hasAutoIncrement): bool|string
	{
		if (!($connection = $this->initPDOStatement())) {
			return false;
		}
		if (($result = $this->prepare->execute($this->params)) === false) {
			return $this->addError($connection->errorInfo()[2], 'mysql');
		}
		if ($isInsert === false) {
			return true;
		}
		$result = $connection->lastInsertId();
		if ($result == 0 && $hasAutoIncrement->isAutoIncrement()) {
			return $this->addError($connection->errorInfo()[2], 'mysql');
		}
		return $result == 0 ? true : $result;
	}


	/**
	 * 重新构建
	 * @throws
	 */
	private function initPDOStatement(): PDO|bool|null
	{
		if (empty($this->sql)) {
			return $this->addError('no sql.', 'mysql');
		}
		if (!(($connect = $this->db->getConnect($this->sql)) instanceof PDO)) {
			return $this->addError('get client error.', 'mysql');
		}
		$this->prepare = $connect->prepare($this->sql);
		if (!($this->prepare instanceof PDOStatement)) {
			return $this->addError($this->sql . ':' . $this->getError(), 'mysql');
		}
		return $connect;
	}

	/**
	 * @param $modelName
	 * @return $this
	 */
	public function setModelName($modelName): static
	{
		$this->_modelName = $modelName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModelName(): string
	{
		return $this->_modelName;
	}

	/**
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function delete(): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param null $scope
	 * @param bool $insert
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function exec($scope = null, $insert = false): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE, $insert, $scope);
	}

	/**
	 * @param array|null $data
	 * @return $this
	 */
	public function bindValues(array $data = NULL): static
	{
		if (!is_array($this->params)) {
			$this->params = [];
		}
		if (!empty($data)) {
			$this->params = array_merge($this->params, $data);
		}
		return $this;
	}

	/**
	 * @param $sql
	 * @return $this
	 * @throws Exception
	 */
	public function setSql($sql): static
	{
		$this->sql = $sql;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getError(): string
	{
		return $this->prepare->errorInfo()[2] ?? 'Db 驱动错误.';
	}

}
