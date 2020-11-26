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
	 * @return bool|PDOStatement
	 * @throws
	 */
	public function incrOrDecr()
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param bool $isInsert
	 * @param bool $hasAutoIncrement
	 * @return bool|string
	 * @throws
	 */
	public function save($isInsert = TRUE, $hasAutoIncrement = true)
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
	public function update($model, $attributes, $condition, $param)
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
	public function batchUpdate($tableName, $attributes, $condition)
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
	public function batchInsert($tableName, $attributes)
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
	public function insert($tableName, $attributes, $param)
	{
		$change = $this->db->getSchema()->getChange();
		$sql = $change->insert($tableName, $attributes, $param);
		return $this->setSql($sql)->bindValues($param);
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function all()
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
	public function mathematics($tableName, $param, $condition)
	{
		$change = $this->db->getSchema()->getChange();
		$sql = $change->mathematics($tableName, $param, $condition);
		return $this->setSql($sql);
	}

	/**
	 * @return array|mixed
	 * @throws Exception
	 */
	public function one()
	{
		return $this->execute(static::FETCH);
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function fetchColumn()
	{
		return $this->execute(static::FETCH_COLUMN);
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function rowCount()
	{
		return $this->execute(static::ROW_COUNT);
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function flush()
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param $type
	 * @param $isInsert
	 * @param $hasAutoIncrement
	 * @return bool|int
	 * @throws Exception
	 */
	private function execute($type, $isInsert = null, $hasAutoIncrement = true)
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
	 * @return array|int|mixed
	 * @throws Exception
	 */
	private function search($type)
	{
		$connect = $this->db->getConnect($this->sql);
		if (!($connect instanceof PDO)) {
			return null;
		}
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
	private function insert_or_change($isInsert, $hasAutoIncrement)
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
	private function initPDOStatement()
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
	public function setModelName($modelName)
	{
		$this->_modelName = $modelName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->_modelName;
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function delete()
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @return bool|int
	 * @throws Exception
	 */
	public function exec()
	{
		return $this->execute(static::EXECUTE);
	}

	/**
	 * @param array|null $data
	 * @return $this
	 */
	public function bindValues(array $data = NULL)
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
	public function setSql($sql)
	{
		$this->sql = $sql;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->prepare->errorInfo()[2] ?? 'Db 驱动错误.';
	}

}
