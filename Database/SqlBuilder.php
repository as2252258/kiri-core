<?php


namespace Database;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;


/**
 * Class SqlBuilder
 * @package Database
 */
class SqlBuilder extends Component
{

	use Builder;


	public ActiveQuery $query;


	/**
	 * @param $query
	 * @return $this
	 */
	public static function builder($query): static
	{
		return new static(['query' => $query]);
	}


	/**
	 * @param array $attributes
	 * @return bool|array
	 * @throws Exception
	 */
	public function update(array $attributes): bool|array
	{
		[$string, $array] = $this->builderParams($attributes);
		if (empty($string) || empty($array)) {
			return $this->addError('None data update.');
		}

		var_dump($string);

		$update = 'UPDATE ' . $this->tableName() . ' SET ' . $string . $this->conditionToString();
		$update .= $this->builderLimit($this->query);

		return [$update, $array];
	}


	/**
	 * @param array $attributes
	 * @param false $isBatch
	 * @return array
	 * @throws Exception
	 */
	public function insert(array $attributes, $isBatch = false): array
	{
		$update = sprintf('INSERT INTO %s', $this->tableName());
		if ($isBatch === true) {
			$attributes = [$attributes];
		}
		$update .= '(' . implode(',', $this->getFields($attributes)) . ') VALUES ';

		$order = 0;
		$keys = $params = [];
		foreach ($attributes as $attribute) {
			[$_keys, $params] = $this->builderParams($attribute, true, $params, $order);

			$keys[] = implode(',', $_keys);
			$order++;
		}
		return [$update . '(' . implode('),(', $keys) . ')', $params];
	}


	/**
	 * @param $attributes
	 * @return array
	 */
	#[Pure] private function getFields($attributes): array
	{
		return array_keys(current($attributes));
	}


	/**
	 * @param array $attributes
	 * @param bool $isInsert
	 * @param array $params
	 * @param int $order
	 * @return array[]
	 * a=:b,
	 */
	private function builderParams(array $attributes, bool $isInsert = false, $params = [], $order = 0): array
	{
		$keys = [];
		foreach ($attributes as $key => $value) {
			if ($isInsert === true) {
				$keys[] = ':' . $key . $order;
			} else {
				$keys[] = $key . '=:' . $key . $order;
			}
			$params[$key . $order] = $value;
		}
		return [$keys, $params];
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function one(): string
	{
		return $this->_prefix(true) . ' LIMIT 0,1';
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function all(): string
	{
		return $this->_prefix(true) . $this->builderLimit($this->query);
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function count(): string
	{
		return $this->_prefix();
	}


	/**
	 * @param bool $hasOrder
	 * @return string
	 * @throws Exception
	 */
	private function _prefix($hasOrder = false): string
	{
		$select = $this->builderSelect($this->query->select) . ' FROM ' . $this->tableName();
		if (!empty($condition = $this->conditionToString())) {
			$select = sprintf('%s %s', $select, $condition);
		}
		if (!empty($this->query->group)) {
			$select .= ' GROUP BY ' . $this->query->group;
		}
		if ($hasOrder === true && !empty($this->query->order)) {
			$select .= $this->builderOrder($this->query->order);
		}
		return $select;
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function truncate(): string
	{
		return sprintf('TRUNCATE %s', $this->tableName());
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	private function conditionToString(): string
	{
		return $this->where($this->query->where);
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function tableName(): string
	{
		return $this->query->modelClass::getTable();
	}

}
