<?php
declare(strict_types=1);

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
	public function builder(): string
	{
		return 'OR ' . $this->resolve($this->column, $this->value, $this->opera);
	}

}
