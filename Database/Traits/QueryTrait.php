<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:56
 */
declare(strict_types=1);

namespace Database\Traits;


use Database\ActiveQuery;
use Database\ActiveRecord;
use Database\Orm\Select;
use Exception;

/**
 * Trait QueryTrait
 * @package Database\Traits
 */
trait QueryTrait
{
	public array $where = [];
	public array $select = [];
	public array $join = [];
	public array $order = [];
	public ?int $offset = NULL;
	public ?int $limit = NULL;
	public string $group = '';
	public string $from = '';
	public string $alias = 't1';
	public array $filter = [];

	public bool $ifNotWhere = false;

	/**
	 * @var ActiveRecord
	 */
	public ActiveRecord $modelClass;

	/**
	 * clear
	 */
	public function clear()
	{
		$this->where = [];
		$this->select = [];
		$this->join = [];
		$this->order = [];
		$this->offset = NULL;
		$this->limit = NULL;
		$this->group = '';
		$this->from = '';
		$this->alias = 't1';
		$this->filter = [];
	}

	/**
	 * @param $bool
	 * @return $this
	 */
	public function ifNotWhere($bool): static
	{
		$this->ifNotWhere = $bool;
		return $this;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getTable(): string
	{
		return $this->modelClass::getTable();
	}


	/**
	 * @param $column
	 * @return $this
	 */
	public function isNull($column): static
	{
		$this->where[] = $column . ' IS NULL';
		return $this;
	}


	/**
	 * @param $column
	 * @return $this
	 */
	public function isEmpty($column): static
	{
		$this->where[] = $column . ' = \'\'';
		return $this;
	}

	/**
	 * @param $column
	 * @return $this
	 */
	public function isNotEmpty($column): static
	{
		$this->where[] = $column . ' <> \'\'';
		return $this;
	}

	/**
	 * @param $column
	 * @return $this
	 */
	public function isNotNull($column): static
	{
		$this->where[] = $column . ' IS NOT NULL';
		return $this;
	}

	/**
	 * @param $columns
	 * @return $this
	 * @throws Exception
	 */
	public function filter($columns): static
	{
		if (!$columns) {
			return $this;
		}
		if (is_callable($columns, TRUE)) {
			return call_user_func($columns, $this);
		}
		if (is_string($columns)) {
			$columns = explode(',', $columns);
		}
		if (!is_array($columns)) {
			return $this;
		}
		$this->filter = $columns;
		return $this;
	}

	/**
	 * @param string $alias
	 *
	 * @return $this
	 *
	 * select * from tableName as t1
	 */
	public function alias($alias = 't1'): static
	{
		$this->alias = $alias;
		return $this;
	}

	/**
	 * @param string|\Closure $tableName
	 *
	 * @return $this
	 */
	public function from(string|\Closure $tableName): static
	{
		$this->from = $tableName;
		return $this;
	}

	/**
	 * @param string $tableName
	 * @param string $alias
	 * @param null $on
	 * @param array|null $param
	 * @return $this
	 * $query->join([$tableName, ['userId'=>'uuvOd']], $param)
	 * $query->join([$tableName, ['userId'=>'uuvOd'], $param])
	 * $query->join($tableName, ['userId'=>'uuvOd',$param])
	 */
	private function join(string $tableName, string $alias, $on = NULL, array $param = NULL): static
	{
		if (empty($on)) {
			return $this;
		}
		$join[] = $tableName . ' AS ' . $alias;
		$join[] = 'ON ' . $this->onCondition($alias, $on);
		if (empty($join)) {
			return $this;
		}

		$this->join[] = implode(' ', $join);
		if (!empty($param)) {
			$this->addParams($param);
		}

		return $this;
	}

	/**
	 * @param $alias
	 * @param $on
	 * @return string
	 */
	private function onCondition($alias, $on): string
	{
		$array = [];
		foreach ($on as $key => $item) {
			if (!str_contains($item, '.')) {
				$this->addParam($key, $item);
			} else {
				$explode = explode('.', $item);
				if (isset($explode[1]) && ($explode[0] == $alias || $this->alias == $explode[0])) {
					$array[] = $key . '=' . $item;
				} else {
					$this->addParam($key, $item);
				}
			}
		}
		return implode(' AND ', $array);
	}

	/**
	 * @param $tableName
	 * @param $alias
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws Exception
	 */
	public function leftJoin(string $tableName, string $alias, $onCondition, $param = NULL): static
	{
		if (class_exists($tableName)) {
			if (!in_array(ActiveRecord::class, class_implements($tableName))) {
				throw new Exception('Model must implement ' . $tableName);
			}
			$tableName = $tableName::getTable();
		}
		return $this->join(...["LEFT JOIN " . $tableName, $alias, $onCondition, $param]);
	}

	/**
	 * @param $tableName
	 * @param $alias
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws Exception
	 */
	public function rightJoin($tableName, $alias, $onCondition, $param = NULL): static
	{
		if (class_exists($tableName)) {
			if (!in_array(ActiveRecord::class, class_implements($tableName))) {
				throw new Exception('Model must implement ' . $tableName);
			}
			$tableName = $tableName::getTable();
		}
		return $this->join(...["RIGHT JOIN " . $tableName, $alias, $onCondition, $param]);
	}

	/**
	 * @param $tableName
	 * @param $alias
	 * @param $onCondition
	 * @param null $param
	 * @return $this
	 * @throws Exception
	 */
	public function innerJoin($tableName, $alias, $onCondition, $param = NULL): static
	{
		if (class_exists($tableName)) {
			if (!in_array(ActiveRecord::class, class_implements($tableName))) {
				throw new Exception('Model must implement ' . $tableName);
			}
			$tableName = $tableName::getTable();
		}
		return $this->join(...["INNER JOIN " . $tableName, $alias, $onCondition, $param]);
	}

	/**
	 * @param $array
	 *
	 * @return string
	 */
	private function toString($array): string
	{
		$tmp = [];
		if (!is_array($array)) {
			return $array;
		}
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$tmp[] = $this->toString($array);
			} else {
				$tmp[] = $key . '=:' . $key;
				$this->attributes[':' . $key] = $val;
			}
		}
		return implode(' AND ', $tmp);
	}

	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function sum($field): static
	{
		$this->select[] = 'SUM(' . $field . ') AS ' . $field;
		return $this;
	}

	/**
	 * @param $field
	 * @return $this
	 */
	public function max($field): static
	{
		$this->select[] = 'MAX(' . $field . ') AS ' . $field;
		return $this;
	}

	/**
	 * @param string $lngField
	 * @param string $latField
	 * @param int $lng1
	 * @param int $lat1
	 *
	 * @return $this
	 */
	public function distance(string $lngField, string $latField, int $lng1, int $lat1): static
	{
		$sql = "ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(($lat1 * PI() / 180 - $lat1 * PI() / 180) / 2),2) + COS($lat1 * PI() / 180) * COS($latField * PI() / 180) * POW(SIN(($lng1 * PI() / 180 - $lngField * PI() / 180) / 2),2))) * 1000) AS distance";
		$this->select[] = $sql;
		return $this;
	}

	/**
	 * @param        $column
	 * @param string $sort
	 *
	 * @return $this
	 *
	 * [
	 *     'addTime',
	 *     'descTime desc'
	 * ]
	 */
	public function orderBy($column, $sort = 'DESC'): static
	{
		if (empty($column)) {
			return $this;
		}
		if (is_string($column)) {
			return $this->addOrder(...func_get_args());
		}

		foreach ($column as $key => $val) {
			$this->addOrder($val);
		}

		return $this;
	}

	/**
	 * @param        $column
	 * @param string $sort
	 *
	 * @return $this
	 *
	 */
	private function addOrder($column, $sort = 'DESC'): static
	{
		$column = trim($column);

		if (func_num_args() == 1 || str_contains($column, ' ')) {
			$this->order[] = $column;
		} else {
			$this->order[] = "$column $sort";
		}
		return $this;
	}

	/**
	 * @param array|string $column
	 *
	 * @return $this
	 */
	public function select($column = '*'): static
	{
		if ($column == '*') {
			$this->select = $column;
		} else {
			if (!is_array($column)) {
				$column = explode(',', $column);
			}
			foreach ($column as $key => $val) {
				$this->select[] = $val;
			}
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function orderRand(): static
	{
		$this->order[] = 'RAND()';
		return $this;
	}

	/**
	 * @param array $conditionArray
	 *
	 * @return $this|array|ActiveQuery
	 * @throws Exception
	 */
	public function or(array $conditionArray = []): static
	{
		$conditions = [];
		if (empty($conditionArray) || count($conditionArray) < 2) {
			return $this;
		}

		$select = new Select();

		$conditions[] = $select->getWhere($this->where);
		$conditions[] = $select->getWhere($conditionArray);
		if (empty($conditions[count($conditions) - 1])) {
			return $this;
		}
		$this->where = ['(' . implode(' OR ', $conditions) . ')'];

		return $this;
	}

	/**
	 * @param        $columns
	 * @param string $oprea
	 * @param null $value
	 *
	 * @return array|ActiveQuery|mixed
	 * @throws Exception
	 */
	public function and($columns, $value = NULL, $oprea = '='): static
	{
		if (!is_numeric($value) && !is_bool($value)) {
			$value = '\'' . $value . '\'';
		}
		$this->where[] = $columns . $oprea . $value;
		return $this;
	}

	/**
	 * @param $limit
	 * @return $this
	 */
	public function plunk($limit): static
	{
		$this->offset = 0;
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param $columns
	 * @param string $value
	 * @return $this
	 * @throws Exception
	 */
	public function like($columns, string $value): static
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['LIKE', $columns, $value];

		return $this;
	}

	/**
	 * @param $columns
	 * @param string $value
	 * @return $this
	 * @throws Exception
	 */
	public function lLike($columns, string $value): static
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['LLike', $columns, rtrim($value, '%')];

		return $this;
	}

	/**
	 * @param $columns
	 * @param string $value
	 * @return $this
	 * @throws Exception
	 */
	public function rLike($columns, string $value): static
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['RLike', $columns, ltrim($value, '%')];

		return $this;
	}


	/**
	 * @param $columns
	 * @param string $value
	 * @return $this
	 * @throws Exception
	 */
	public function notLike($columns, string $value): static
	{
		if (empty($columns) || empty($value)) {
			return $this;
		}

		if (is_array($columns)) {
			$columns = 'CONCAT(' . implode(',^,', $columns) . ')';
		}

		$this->where[] = ['NOT LIKE', $columns, ltrim($value, '%')];

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function eq(string $column, int $value): static
	{
		$this->where[] = ['EQ', $column, $value];

		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function neq(string $column, int $value): static
	{
		$this->where[] = ['NEQ', $column, $value];

		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function gt(string $column, int $value): static
	{
		$this->where[] = ['GT', $column, $value];

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function egt(string $column, int $value): static
	{
		$this->where[] = ['EGT', $column, $value];

		return $this;
	}


	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function lt(string $column, int $value): static
	{
		$this->where[] = ['LT', $column, $value];

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 */
	public function elt(string $column, int $value): static
	{
		$this->where[] = ['ELT', $column, $value];

		return $this;
	}

	/**
	 * @param $columns
	 * @param $value
	 * @return $this
	 * @throws Exception
	 */
	public function in($columns, $value): static
	{
		if ($value instanceof \Closure) {
			$value = $this->makeNewQuery($value);
		} else if (empty($value) || !is_array($value)) {
			$value = [-1];
		} else {
			$value = array_filter($value, function ($value) {
				return $value !== null;
			});
			$value = array_unique($value);
		}
		$this->where[] = ['IN', $columns, $value];
		return $this;
	}


	/**
	 * @param $value
	 * @return ActiveQuery
	 * @throws Exception
	 */
	public function makeNewQuery($value): ActiveQuery
	{
		$activeQuery = new ActiveQuery($this->modelClass);
		call_user_func($value, $activeQuery);
		if (empty($activeQuery->from)) {
			$activeQuery->from($activeQuery->modelClass::getTable());
		}
		return $activeQuery;
	}


	/**
	 * @param $columns
	 * @param $value
	 * @return $this
	 * @throws Exception
	 */
	public function notIn($columns, $value): static
	{
		$this->where[] = ['NOT IN', $columns, $value];

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $start
	 * @param string $end
	 * @return $this
	 * @throws Exception
	 */
	public function between(string $column, string $start, string $end): static
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}
		$this->where[] = ['BETWEEN', $column, [$start, $end]];

		return $this;
	}

	/**
	 * @param string $column
	 * @param string $start
	 * @param string $end
	 * @return $this
	 * @throws Exception
	 */
	public function notBetween(string $column, string $start, string $end): static
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}

		$this->where[] = ['NOT BETWEEN', $column, [$start, $end]];

		return $this;
	}

	/**
	 * @param array $params
	 *
	 * @return $this
	 */
	public function bindParams(array $params = []): static
	{
		if (empty($params)) {
			return $this;
		}
		$this->attributes = $params;
		return $this;
	}

	/**
	 * @param callable|array|string $conditions
	 * @return $this
	 */
	public function where(callable|array|string $conditions): static
	{
		if ($conditions instanceof \Closure) {
			call_user_func($conditions, $this);
		} else {
			if (is_string($conditions)) {
				$conditions = [$conditions];
			}
			$this->where[] = $conditions;
		}
		var_dump($this->where);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string|null $having
	 *
	 * @return $this
	 */
	public function groupBy(string $name, string $having = NULL): static
	{
		$this->group = $name;
		if (empty($having)) {
			return $this;
		}
		$this->group .= ' HAVING ' . $having;
		return $this;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return $this
	 */
	public function limit(int $offset, int $limit = 20): static
	{
		$this->offset = $offset;
		$this->limit = $limit;
		return $this;
	}


	/**
	 * @return $this
	 */
	public function oneLimit(): static
	{
		$this->limit = 1;
		return $this;
	}

}
