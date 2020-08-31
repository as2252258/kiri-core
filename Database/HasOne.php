<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:47
 */

namespace Database;

use Exception;

/**
 * Class HasOne
 * @package Database
 * @internal Query
 */
class HasOne extends HasBase
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
		return $this->_relation->first($this->model::className(), $this->value);
	}
}
