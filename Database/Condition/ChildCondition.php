<?php


namespace Database\Condition;


/**
 * Class ChildCondition
 * @package Database\Condition
 */
class ChildCondition extends Condition
{

	/**
	 * @return string
	 */
	public function builder()
	{
		return $this->column . ' ' . $this->opera . ' (' . $this->value . ')';
	}

}
