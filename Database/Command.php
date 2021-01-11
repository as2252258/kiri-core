<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 15:23
 */
declare(strict_types=1);
namespace Database;


use Snowflake\Abstracts\Component;
use Exception;
use PDO;
use PDOStatement;
use Snowflake\Abstracts\Config;

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
	public function save($isInsert = TRUE, $hasAutoIncrement = true): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE, $isInsert, $hasAutoIncrement);
	}

	/**
	 * @param $model
	 * @param $attributes
	 * @param $condition
	 * @param $param
	 * @return Command
	 * @throws Exception
	 */
	public function update($model, $attributes, $condition, $param): Command
	{
		$change = $this->db->getSchema()->getChange();
		$sql = $change->update($model, $attributes, $condition, $param);
		return $this->setSql($sql)->bindValues($param);
	}


	/**
	 * @param $tableName
	 * @param $attributes
	 * @param $condition
	 * @return Command
	 * @throws Exception
	 */
	public function batchUpdate($tableName, $attributes, $condition): Command
	{
		$change = $this->db->getSchema()->getChange();
		[$sql, $param] = $change->batchUpdate($tableName, $attributes, $condition);
		return $this->setSql($sql)->bindValues($param);
	}


	/**
	 * @param $tableName
	 * @param $attributes
	 * @return Command
	 * @throws Exception
	 */
	public function batchInsert($tableName, $attributes): Command
	{
		$change = $this->db->getSchema()->getChange();
		$attribute_key = array_keys(current($attributes));
		[$sql, $param] = $change->batchInsert($tableName, $attribute_key, $attributes);
		return $this->setSql($sql)->bindValues($param);
	}

	/**
	 * @param $tableName
	 * @param $attributes
	 * @param $param
	 * @return Command
	 * @throws Exception
	 */
	public function insert($tableName, $attributes, $param): Command
	{
		$change = $this->db->getSchema()->getChange();
		$sql = $change->insert($tableName, $attributes, $param);
		return $this->setSql($sql)->bindValues($param);
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
	 * @param $tableName
	 * @param $param
	 * @param $condition
	 * @return Command
	 * @throws Exception
	 */
	public function mathematics($tableName, $param, $condition): static
	{
		$change = $this->db->getSchema()->getChange();
		$sql = $change->mathematics($tableName, $param, $condition);
		return $this->setSql($sql);
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
	private function execute($type, $isInsert = null, $hasAutoIncrement = true): int|bool|array|string|null
	{
		try {
			$time = microtime(true);
			if ($type === static::EXECUTE) {
				$result = $this->insert_or_change($isInsert, $hasAutoIncrement);
			} else {
				$result = $this->search($type);
			}
			if ($this->prepare) {
				$this->prepare->closeCursor();
			}
			if (Config::get('debug.enable', false, false)) {
				$this->debug($this->sql . '。 Run-time: ' . (microtime(true) - $time));
			}
		} catch (\Throwable $exception) {
			$result = $this->addError($this->sql . '. error: ' . $exception->getMessage(), 'mysql');
		} finally {
			return $result;
		}
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
			return null;
		}
		var_dump($this->sql);
		if (!($query = $connect->query($this->sql))) return null;
		if ($type === static::ROW_COUNT) {
			$result = $query->rowCount();
		} else if ($type === static::FETCH_COLUMN) {
			$result = $query->fetchColumn();
		} else if ($type === static::FETCH_ALL) {
			$result = $query->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$result = $query->fetch(PDO::FETCH_ASSOC);
		}
		return $result;
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
		if (!$isInsert) {
			return true;
		}
		$result = $connection->lastInsertId();
		if ($result == 0 && $hasAutoIncrement) {
			return $this->addError($connection->errorInfo()[2], 'mysql');
		}
		return $result;
	}


	/**
	 * 重新构建
	 * @throws
	 */
	private function initPDOStatement(): PDO|bool|null
	{
		if (empty($this->sql)) {
			return null;
		}
		if (!(($connect = $this->db->getConnect($this->sql)) instanceof PDO)) {
			return null;
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
	 * @return int|bool|array|string|null
	 * @throws Exception
	 */
	public function exec(): int|bool|array|string|null
	{
		return $this->execute(static::EXECUTE);
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
