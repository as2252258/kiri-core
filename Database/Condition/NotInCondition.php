<?php
declare(strict_types=1);

namespace Database\Condition;

/**
 * Class NotInCondition
 * @package Database\Condition
 */
class NotInCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{

		$format = array_filter($this->format($this->value));
		if (empty($format)) {
			return '';
		}

		return '`' . $this->column . '` not in(' . implode(',', $format) . ')';
	}

}
