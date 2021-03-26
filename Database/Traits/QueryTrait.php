<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:56
 */
declare(strict_types=1);

namespace Database\Traits;


use Closure;
use Database\ActiveQuery;
use Database\ActiveRecord;
use Database\Condition\MathematicsCondition;
use Database\Sql;
use Exception;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

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
	public string|Closure|ActiveQuery $from = '';
	public string $alias = 't1';
	public array $filter = [];

	public bool $ifNotWhere = false;

	/**
	 * @var ?ActiveRecord
	 */
	public ?ActiveRecord $modelClass;

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
	 * @param string $column
	 * @param callable $callable
	 * @return $this
	 */
	public function case(string $column, callable $callable): static
	{
		$caseWhen = new When($column, $this);

		call_user_func($callable, $caseWhen);

		$this->where[] = $caseWhen->end();

		return $this;
	}


	/**
	 * @param $condition
	 * @param $condition1
	 * @param $condition2
	 * @return $this
	 * @throws Exception
	 */
	public function if(string|array $condition, string|array|Closure $condition1, string|array|Closure $condition2): static
	{
		if (!is_string($condition)) {
			$condition = $this->makeClosureFunction($condition, true);
		}
		if (!is_string($condition1)) {
			$condition1 = $this->makeClosureFunction($condition1, true);
		}
		if (!is_string($condition2)) {
			$condition2 = $this->makeClosureFunction($condition2, true);
		}
		$this->where[] = 'IF(' . $condition . ', ' . $condition1 . ', ' . $condition2 . ')';
		return $this;
	}


	/**
	 * @param $condition
	 * @return string
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function conditionToString($condition): string
	{
		$newSql = $this->makeNewSqlGenerate();
		if ($condition instanceof Closure) {
			call_user_func($condition, $newSql);
		} else {
			$newSql->where($condition);
		}
		return $newSql->getCondition();
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
	 * @param string $column
	 * @param string $value
	 * @return $this
	 */
	public function locate(string $column, string $value): static
	{
		$this->where[] = 'LOCATE(' . $column . ',\'' . addslashes($value) . '\') > 0';
		return $this;
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
	 * @param string|Closure $tableName
	 *
	 * @return $this
	 */
	public function from(string|Closure $tableName): static
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
	 * @return QueryTrait
	 */
	public function or(array $conditionArray = []): static
	{
		$this->where = ['or', $conditionArray];
		return $this;
	}

	/**
	 * @param string $columns
	 * @param string|int|bool|null $value
	 *
	 * @param string $opera
	 * @return QueryTrait
	 */
	public function and(string $columns, string|int|null|bool $value = NULL, $opera = '='): static
	{
		if (!is_numeric($value) && !is_bool($value)) {
			$value = '\'' . $value . '\'';
		}
		$this->where[] = $columns . $opera . $value;
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

		$this->where[] = $columns . ' LIKE \'%' . addslashes($value) . '%\'';

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

		$this->where[] = $columns . ' LLike \'%' . addslashes($value) . '\'';

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

		$this->where[] = $columns . ' RLike \'' . addslashes($value) . '%\'';

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

		$this->where[] = $columns . ' NOT LIKE \'%' . addslashes($value) . '%\'';

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return $this
	 * @throws Exception
	 * @see MathematicsCondition
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
	 * @see MathematicsCondition
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
	 * @see MathematicsCondition
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
	 * @see MathematicsCondition
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
	 * @see MathematicsCondition
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
	 * @see MathematicsCondition
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
		if (empty($value) || !is_array($value)) {
			$value = [-1];
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
	 * @return Sql
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function makeNewSqlGenerate(): Sql
	{
		return Snowflake::createObject(Sql::class);
	}


	/**
	 * @param $columns
	 * @param $value
	 * @return $this
	 * @throws Exception
	 */
	public function notIn($columns, $value): static
	{
		if (empty($value) || !is_array($value)) {
			$value = [-1];
		}
		$this->where[] = ['NOT IN', $columns, $value];
		return $this;
	}

	/**
	 * @param string $column
	 * @param int $start
	 * @param int $end
	 * @return $this
	 */
	public function between(string $column, int $start, int $end): static
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}

		$this->where[] = $column . ' BETWEEN' . $start . ' AND ' . $end;

		return $this;
	}

	/**
	 * @param string $column
	 * @param int $start
	 * @param int $end
	 * @return $this
	 */
	public function notBetween(string $column, int $start, int $end): static
	{
		if (empty($column) || empty($start) || empty($end)) {
			return $this;
		}

		$this->where[] = $column . 'NOT BETWEEN' . $start . ' AND ' . $end;

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
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function where(callable|array|string $conditions): static
	{
		if ($conditions instanceof Closure) {
			$conditions = $this->makeClosureFunction($conditions);
		}
		$this->where[] = $conditions;
		return $this;
	}


	/**
	 * @param Closure|array $closure
	 * @param bool $onlyWhere
	 * @return string
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function makeClosureFunction(Closure|array $closure, $onlyWhere = false): string
	{
		$generate = $this->makeNewSqlGenerate();
		call_user_func($closure, $generate);
		if ($onlyWhere === true) {
			return $generate->getCondition();
		}
		return $generate->getSql();
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
