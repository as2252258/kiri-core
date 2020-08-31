<?php


namespace Database\Orm;


use Snowflake\Abstracts\BaseObject;
use Database\ActiveQuery;
use Database\Sql;
use Database\Db;
use Exception;

/**
 * Class Select
 * @package Database\Orm
 */
class Select extends BaseObject
{

	use Condition;

	/**
	 * @param ActiveQuery|Db|Sql $query
	 * @return string
	 * @throws Exception
	 */
	public function getQuery($query)
	{
		return $this->generate($query, false);
	}

	/**
	 * @param ActiveQuery|Db $query
	 * @return string
	 * @throws Exception
	 */
	public function count($query)
	{
		return $this->generate($query, true);
	}

	/**
	 * @param ActiveQuery|Db|Sql $query
	 * @param $isCount
	 * @return string
	 * @throws Exception
	 */
	private function generate($query, $isCount = false)
	{
		if (empty($query->from)) {
			$query->from = $query->getTable();
		}
		$builder = array_filter([
			$this->builderSelect($query->select, $isCount),
			$this->builderFrom($query->from),
			$this->builderAlias($query->alias),
			$this->builderJoin($query->join),
			$this->builderWhere($query->where),
			$this->builderGroup($query->group)
		], function ($value) {
			return !empty($value);
		});
		if ($isCount) {
			return implode('', $builder);
		}

		$order = $this->builderOrder($query->order);
		if (!empty($order)) {
			$builder[] = $order;
		}
		$builder[] = $this->builderLimit($query);

		return implode('', $builder);
	}

	/**
	 * @param null $select
	 * @param bool $isCount
	 * @return string
	 */
	private function builderSelect($select = NULL, $isCount = false)
	{
		if ($isCount === true) {
			return 'SELECT COUNT(*)';
		}
		if (empty($select)) {
			return "SELECT *";
		}
		if (is_array($select)) {
			return "SELECT " . implode(',', $select);
		} else {
			return "SELECT " . $select;
		}
	}

	/**
	 * @param $table
	 * @return string
	 */
	public function getColumn($table)
	{
		return 'SHOW FULL FIELDS FROM ' . $table;
	}
}
