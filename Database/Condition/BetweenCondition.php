<?php


namespace Database\Condition;


/**
 * Class BetweenCondition
 * @package Database\Condition
 */
class BetweenCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{
		return $this->column . ' BETWEEN ' . $this->value[0] . ' AND ' . $this->value[1];
	}

}
