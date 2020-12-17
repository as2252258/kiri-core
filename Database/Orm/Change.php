<?php
declare(strict_types=1);

namespace Database\Orm;


use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\BaseObject;
use Database\ActiveQuery;
use Exception;

/**
 * Class Change
 * @package Yoc\db
 */
class Change extends BaseObject
{

	use Condition;


	/**
	 * @param string $model
	 * @param $attributes
	 * @param $condition
	 * @param $params
	 * @return string
	 * @throws Exception
	 */
	public function update(string $model, $attributes, $condition, &$params): string
	{
		if (empty($params)) {
			throw new Exception("Not has update values.");
		}
		$_tmp = [];
		foreach ($attributes as $val) {
			if (!isset($params[$val])) {
				continue;
			}
			$_tmp[] = $val . '=:' . $val;
		}
		if (empty($_tmp)) {
			return '';
		}
		$where = implode(',', $_tmp);
		if (!empty($condition)) {
			$where .= $this->builderWhere($condition);
		}
		return "UPDATE " . $model . ' SET ' . $where;
	}

	/**
	 * @param string $table
	 * @param array $attributes
	 * @param $condition
	 * @return array|string
	 * @throws
	 */
	public function batchUpdate(string $table, array $attributes, $condition): array|string
	{
		$param = [];
		$_attributes = [];
		foreach ($attributes as $key => $val) {
			if ($val === null) {
				continue;
			}
			$_attributes[':' . $key] = $this->valueEncode($val, true);
			$param[] = $key . '=:' . $key;
		}
		if (empty($param)) {
			return '';
		}
		$param = implode(',', $param);
		if (!empty($condition)) {
			$param .= $condition;
		}
		return ['UPDATE ' . $table . ' SET ' . $param, $_attributes];
	}

	/**
	 * @param $table
	 * @param $params
	 * @param $condition
	 * @return string
	 * @throws Exception
	 */
	public function mathematics($table, $params, $condition): string
	{
		$_tmp = $newParam = [];
		if (isset($params['incr']) && is_array($params['incr'])) {
			$_tmp = $this->assemble($params['incr'], ' + ', $_tmp);
		}
		if (isset($params['decr']) && is_array($params['decr'])) {
			$_tmp = $this->assemble($params['decr'], ' - ', $_tmp);
		}
		if (empty($_tmp)) {
			throw new Exception('Not has IncrBy or DecrBy values.');
		}
		$_tmp = implode(',', $_tmp);
		if (!empty($condition)) {
			$_tmp .= $this->builderWhere($condition);
		}
		return 'UPDATE ' . $table . ' SET ' . $_tmp;
	}

	/**
	 * @param $params
	 * @param $op
	 * @param array $_tmp
	 * @return array
	 * @throws Exception
	 */
	private function assemble($params, $op, array $_tmp): array
	{
		$message = 'Incr And Decr action. The value must a numeric.';
		foreach ($params as $key => $val) {
			$_tmp[] = $key . '=' . $key . $op . $val;
			if (!is_numeric($val)) {
				throw new Exception($message);
			}
		}

		return $_tmp;
	}

	/**
	 * @param $table
	 * @param array $params
	 * @return string
	 */
	public function insertOrUpdateByDUPLICATE($table, array $params): string
	{
		$keys = implode(',', array_keys($params));

		$onValues = [];
		$values = array_values($params);
		foreach ($values as $key => $val) {
			$onValues[] = $this->valueEncode($val, true);
		}

		$onUpdates = [];
		foreach ($params as $key => $val) {
			$onUpdates[] = $key . '=' . $this->valueEncode($val, true);
		}
		$newSql = $this->inserts($table, $keys, '(' . implode(',', $onValues) . ')');

		return $newSql . ' ON DUPLICATE KEY UPDATE ' . implode(',', $onUpdates);
	}

	/**
	 * @param $table
	 * @param $attributes
	 * @param array|null $params
	 * @return string
	 * @throws Exception
	 */
	public function insert($table, $attributes, array $params = NULL): string
	{
		$sql = $this->inserts($table, implode(',', $attributes), '(:' . implode(',:', $attributes) . ')');
		if (empty($params)) {
			throw new Exception("save data param not find.");
		}
		foreach ($params as $key => $val) {
			if (!str_contains($sql, ':' . $key)) {
				throw new Exception("save $key data param not find.");
			}
		}
		return $sql;
	}


	/**
	 * @param $table
	 * @param $attributes
	 * @param array|NULL $params
	 * @return array
	 * @throws Exception
	 */
	public function batchInsert($table, $attributes, array $params = NULL): array
	{
		if (empty($params)) {
			throw new Exception("save data param not find.");
		}
		$insert = $insertData = [];
		foreach ($params as $key => $val) {
			if (!is_array($val)) {
				continue;
			}
			array_push($insert, '(:' . implode($key . ',:', $attributes) . $key . ')');
			foreach ($attributes as $myVal) {
				$insertData[':' . $myVal . $key] = $this->valueEncode($val[$myVal], true);
			}
		}
		if (empty($insertData) || empty($insert)) {
			throw new Exception("save data is empty.");
		}
		$sql = $this->inserts($table, implode(',', $attributes), implode(',', $insert));
		return [$sql, $insertData];
	}


	/**
	 * @param $table
	 * @param $fields
	 * @param $data
	 * @return string
	 * 构建SQL语句
	 */
	#[Pure] private function inserts($table, $fields, $data): string
	{
		$query = [
			'INSERT IGNORE INTO', '%s', '(%s)', 'VALUES %s'
		];
		$query = implode(' ', $query);

		return sprintf($query, $table, $fields, $data);
	}


	/**
	 * @param $table
	 * @param $attributes
	 * @param $condition
	 * @return bool|string
	 * @throws Exception
	 */
	public function updateAll($table, $attributes, $condition): bool|string
	{
		$param = [];
		foreach ($attributes as $key => $val) {
			if ($val === null || $val === '') {
				continue;
			}
			$param[] = $key . '=' . $this->valueEncode($val);
		}
		if (empty($param)) return true;

		$param = implode(',', $param);
		if (!empty($condition)) {
			$param .= $this->builderWhere($condition);
		}
		return 'UPDATE ' . $table . ' SET ' . $param;
	}


	/**
	 * @param ActiveQuery $query
	 * @return string
	 * @throws Exception
	 */
	public function delete(ActiveQuery $query): string
	{
		if (empty($query->from)) {
			$query->from = $query->getTable();
		}

		$condition = $this->builderWhere($query->where);
		if (empty($condition) && !$query->ifNotWhere) {
			throw new Exception('clear data must has condition.');
		}
		$query = $this->builderFrom($query->from) . $condition;

		return 'DELETE ' . $query;
	}

	/**
	 * @param string $tableName
	 * @return string
	 */
	public function truncate(string $tableName): string
	{
		return 'TRUNCATE ' . $tableName;
	}


}
