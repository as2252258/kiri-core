<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 13:58
 */
declare(strict_types=1);
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
	 * @return static
	 * @throws Exception
	 */
	public function __call($name, $arguments): static
	{
		$this->_relation->getQuery($this->model::className())->$name(...$arguments);
		return $this;
	}

	/**
	 * @return array|null|ActiveRecord
	 * @throws Exception
	 */
	public function get(): array|ActiveRecord|null
	{
		return $this->_relation->get($this->model::className(), $this->value);
	}
}
