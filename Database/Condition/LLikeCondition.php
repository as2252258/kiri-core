<?php
declare(strict_types=1);

namespace Database\Condition;

use Snowflake\Core\Str;

/**
 * Class LLikeCondition
 * @package Database\Condition
 */
class LLikeCondition extends Condition
{

	public string $pos = '';

	/**
	 * @return string
	 */
	public function builder()
	{
		if (!is_string($this->value)) {
			$this->value = array_shift($this->value);
		}
		$this->value = Str::encode($this->value);
		return $this->column . ' LIKE \'%' . $this->value . '\'';
	}

}
