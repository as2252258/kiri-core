<?php
declare(strict_types=1);

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
		return $this->_relation->count($this->model::className(), $this->value);
	}

}
