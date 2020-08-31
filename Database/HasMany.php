<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:58
 */

namespace Database;

use Exception;

/**
 * Class HasMany
 * @package Database
 *
 * @method with($name)
 */
class HasMany extends HasBase
{

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
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
		return $this->_relation->get($this->model::className(), $this->value);
	}
}
