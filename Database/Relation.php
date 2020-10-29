<?php
declare(strict_types=1);

namespace Database;


use Snowflake\Abstracts\Component;

/**
 * Class Relation
 * @package Snowflake\db
 */
class Relation extends Component
{

	private array $_relations = [];

	/** @var ActiveQuery[] $_query */
	private array $_query = [];

	/**
	 * @param string $identification
	 * @param ActiveQuery $query
	 * @return $this
	 */
	public function bindIdentification(string $identification, ActiveQuery $query)
	{
		$this->_query[$identification] = $query;
		return $this;
	}

	/**
	 * @param $name
	 * @return ActiveQuery|null
	 */
	public function getQuery(string $name)
	{
		return $this->_query[$name] ?? null;
	}


	/**
	 * @param $identification
	 * @param $localValue
	 * @return ActiveRecord|mixed
	 * @throws
	 */
	public function first(string $identification, $localValue)
	{
		$_identification = $identification . '_count_' . $localValue;
		if (isset($this->_relations[$_identification]) && $this->_relations[$_identification] !== null) {
			return $this->_relations[$_identification];
		}

		$activeModel = $this->_query[$identification]->first();
		if (empty($activeModel)) {
			return null;
		}

		return $this->_relations[$_identification] = $activeModel;
	}


	/**
	 * @param $identification
	 * @param $localValue
	 * @return ActiveRecord|mixed
	 * @throws
	 */
	public function count(string $identification, $localValue)
	{
		$_identification = $identification . '_' . $localValue;
		if (isset($this->_relations[$_identification]) && $this->_relations[$_identification] !== null) {
			return $this->_relations[$_identification];
		}

		$activeModel = $this->_query[$identification]->count();
		if (empty($activeModel)) {
			return null;
		}

		return $this->_relations[$_identification] = $activeModel;
	}


	/**
	 * @param $identification
	 * @param $localValue
	 * @return array|Collection|mixed|null
	 * @throws
	 */
	public function get(string $identification, $localValue)
	{
		if (is_array($localValue)) {
			$_identification = $identification . '_' . implode('_', $localValue);
		} else {
			$_identification = $identification . '_' . $localValue;
		}
		if (isset($this->_relations[$_identification]) && $this->_relations[$_identification] !== null) {
			return $this->_relations[$_identification];
		}

		$activeModel = $this->_query[$identification]->get();
		if (empty($activeModel)) {
			return null;
		}

		return $this->_relations[$_identification] = $activeModel;
	}

}
