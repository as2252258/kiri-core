<?php


namespace Database\Condition;



/**
 * Class OrCondition
 * @package Database\Condition
 */
class OrCondition extends Condition
{

	/**
	 * @return string
	 */
	public function builder()
	{
		return 'OR ' . $this->resolve($this->column, $this->value, $this->opera);
	}

}
