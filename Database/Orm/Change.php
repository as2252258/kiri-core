<?php
declare(strict_types=1);

namespace Database\Orm;


use Database\Connection;
use Database\Mysql\Schema;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\BaseObject;
use Database\ActiveQuery;
use Exception;
use Snowflake\Snowflake;

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
	 * @param Schema $db
	 * @param array $attributes
	 * @param $condition
	 * @return array|string
	 * @throws Exception
	 */
	public function batchUpdate(string $table, Schema $db, array $attributes, $condition): array|string
	{
		$param = [];
		$_attributes = [];

		$format = $db->getColumns()->table($table);
		foreach ($attributes as $key => $val) {
			if ($val === null) {
				continue;
			}
			$_attributes[':' . $key] = $format->fieldFormat($key, $val);
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
	 * @return string|bool
	 * @throws Exception
	 */
	public function mathematics($table, $params, $condition): string|bool
	{
		$_tmp = $newParam = [];
		if (isset($params['incr']) && is_array($params['incr'])) {
			$_tmp = $this->assemble($params['incr'], ' + ', $_tmp);
		}
		if (isset($params['decr']) && is_array($params['decr'])) {
			$_tmp = $this->assemble($params['decr'], ' - ', $_tmp);
		}
		if (empty($_tmp)) {
			return $this->addError('Not has IncrBy or DecrBy values.');
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
		foreach ($params as $key => $val) {
			$_tmp[] = sprintf('%s=%s%s%d', $key, $key, $op, $val);
		}
		return $_tmp;
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
	 * @param Schema $db
	 * @param array|NULL $params
	 * @return array
	 * @throws Exception
	 */
	public function batchInsert($table, Schema $db, array $params = NULL): array
	{
		if (empty($params)) {
			throw new Exception("save data param not find.");
		}
		$insert = $insertData = [];

		$format = $db->getColumns()->table($table);

		$attributes = array_keys(current($params));

		foreach ($params as $key => $val) {
			if (!is_array($val)) {
				continue;
			}
			array_push($insert, '(:' . implode($key . ',:', $attributes) . $key . ')');
			foreach ($attributes as $myVal) {
				$insertData[':' . $myVal . $key] = $format->fieldFormat($myVal, $val[$myVal]);
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
		return sprintf('INSERT IGNORE INTO' . ' %s (%s) VALUES %s', $table, $fields, $data);
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
