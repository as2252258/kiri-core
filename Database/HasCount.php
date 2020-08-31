<?php


namespace Database;

use Exception;

/**
 * Class HasCount
 * @package Database
 */
class HasCount extends HasBase
{

	/**
	 * @param $name
	 * @param $arguments
	 * @return $this
	 * @throws Exception
	 */
	public function __call($name, $arguments)
	{
		$this->_relation->getQuery($this->model::className())->$name(...$arguments);
		return $this;
	}

	/**
	 * @return array|null|ActiveRecord
	 * @throws Exception
	 */
	public function get()
	{
		return $this->_relation->count($this->model::className(), $this->value);
	}

}
