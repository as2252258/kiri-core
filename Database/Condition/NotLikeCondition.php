<?php


namespace Database\Condition;

use Snowflake\Core\Str;

/**
 * Class NotLikeCondition
 * @package Database\Condition
 */
class NotLikeCondition extends Condition
{

	public $pos = '';

	/**
	 * @return string
	 */
	public function builder()
	{
		if (!is_string($this->value)) {
			$this->value = array_shift($this->value);
		}
		$this->value = Str::encode($this->value);
		return $this->column . ' NOT LIKE \'%' . $this->value . '%\'';
	}

}
