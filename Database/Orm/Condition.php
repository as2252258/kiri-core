<?php
declare(strict_types=1);

namespace Database\Orm;


use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Core\Str;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Database\ActiveQuery;
use Database\Base\ConditionClassMap;
use Database\Condition\HashCondition;
use Database\Sql;
use Database\Traits\QueryTrait;
use Database\Condition\Condition as CCondition;
use Exception;

/**
 * Trait Condition
 * @package Database\Orm
 */
trait Condition
{

	/**
	 * @param $query
	 * @return string
	 * @throws Exception
	 */
	public function getWhere($query): string
	{
		return $this->builderWhere($query);
	}


	/**
	 * @param $alias
	 * @return string
	 */
	private function builderAlias($alias): string
	{
		return " AS " . $alias;
	}

	/**
	 * @param $table
	 * @return string
	 * @throws Exception
	 */
	private function builderFrom($table): string
	{
		if ($table instanceof ActiveQuery) {
			$table = '(' . $table->getBuild()->getQuery($table) . ')';
		}
		return " FROM " . $table;
	}

	/**
	 * @param $join
	 * @return string
	 */
	private function builderJoin($join): string
	{
		if (!empty($join)) {
			return ' ' . implode(' ', $join);
		}
		return '';
	}

	/**
	 * @param $where
	 * @return string
	 * @throws Exception
	 */
	private function builderWhere($where): string
	{
		if (empty($where)) {
			return '';
		}
		if (is_string($where)) {
			return sprintf(' WHERE %s', $where);
		}
		$_tmp = [];
		$where = array_filter($where);
		foreach ($where as $key => $value) {
			if (is_array($value)) {
				$value = $this->arrayMap($value);
			} else if (!is_numeric($key)) {
				$value = $key . '=' . $this->valueEncode($value);
			}
			if ($value === null) {
				continue;
			}
			$_tmp[] = $value;
		}

		if (!empty($_tmp)) {
			return ' WHERE ' . implode(' AND ', $_tmp);
		}
		return '';
	}

	/**
	 * @param $value
	 * @return mixed
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	private function arrayMap($value): mixed
	{
		$classMap = ConditionClassMap::$conditionMap;
		if (isset($value[0])) {
			if (!isset($classMap[strtoupper($value[0])])) {
				return $value[0];
			}
			$value[0] = strtoupper($value[0]);
			$result = $this->classMap($value);
		} else {
			/** @var HashCondition $condition */
			$condition = Snowflake::createObject(HashCondition::class);
			$condition->setValue($value);
			$condition->setOpera('=');
			$result = $condition->builder();
		}
		return $result;
	}

	/**
	 * @param $value
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function classMap($value): mixed
	{
		[$option['opera'], $option['column'], $option['value']] = $value;

		$class = ConditionClassMap::$conditionMap[strtoupper($option['opera'])];
		if (!is_array($class)) {
			$class = ['class' => $class];
		}
		$option = array_merge($option, $class);

		/** @var Condition $class */
		return Snowflake::createObject($option)->builder();
	}

	/**
	 * @param $group
	 * @return string
	 */
	private function builderGroup($group): string
	{
		if (empty($group)) {
			return '';
		}
		return ' GROUP BY ' . $group;
	}

	/**
	 * @param $order
	 * @return string
	 */
	private function builderOrder($order): string
	{
		if (!empty($order)) {
			return ' ORDER BY ' . implode(',', $order);
		} else {
			return '';
		}
	}

	/**
	 * @param ActiveQuery $query
	 * @return string
	 */
	private function builderLimit(ActiveQuery $query): string
	{
		$limit = $query->limit;
		if (!is_numeric($limit) || $limit < 1) {
			return "";
		}
		$offset = $query->offset;

		if ($offset === null) {
			return ' LIMIT ' . $limit;
		}

		return ' LIMIT ' . $offset . ',' . $limit;
	}


	/**
	 * @param $value
	 * @param bool $isSearch
	 * @return int|string
	 */
	public function valueEncode($value, $isSearch = false): int|string
	{
		if ($isSearch) {
			return $value;
		}
		if (is_numeric($value)) {
			return $value;
		} else {
			if (!is_null(Json::decode($value))) {
				return $value;
			}
			return '\'' . Str::encode($value) . '\'';
		}
	}

}
