<?php


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
	public function builder()
	{
		return $this->resolve($this->column, $this->value, $this->opera);
	}

}
