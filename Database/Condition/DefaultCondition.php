<?php
declare(strict_types=1);

namespace Database\Condition;


/**
 * Class DefaultCondition
 * @package Database\Condition
 */
class DefaultCondition extends Condition
{

	/**
	 * @return string
	 */
	public function builder(): string
	{
		return $this->resolve($this->column, $this->value, $this->opera);
	}

}
