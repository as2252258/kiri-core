<?php
declare(strict_types=1);

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
	public function getQuery(ActiveQuery|Sql|Db $query): string
	{
		return $this->generate($query, false);
	}

	/**
	 * @param ActiveQuery|Db|Sql $query
	 * @return string
	 * @throws Exception
	 */
	public function count(ActiveQuery|Db|Sql $query): string
	{
		return $this->generate($query, true);
	}

	/**
	 * @param ActiveQuery|Db|Sql $query
	 * @param bool $isCount
	 * @return string
	 * @throws Exception
	 */
	private function generate(ActiveQuery|Db|Sql $query, $isCount = false): string
	{
		if (empty($query->from)) {
			$query->from = $query->getTable();
		}
		$builder = array_filter([
			$this->builderSelect($query->select, $isCount),
			$this->builderFrom($query->from),
			$this->builderAlias($query->alias),
			$this->builderJoin($query->join),
			$this->where($query->where),
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
	 * @param $table
	 * @return string
	 */
	public function getColumn($table): string
	{
		return 'SHOW FULL FIELDS FROM ' . $table;
	}
}
