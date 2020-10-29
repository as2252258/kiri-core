<?php
declare(strict_types=1);

namespace Database\Condition;

use Database\ActiveQuery;

/**
 * Class InCondition
 * @package Database\Condition
 */
class InCondition extends Condition
{


	/**
	 * @return string
	 * @throws \Exception
	 */
	public function builder()
	{
		if ($this->value instanceof ActiveQuery) {
			$this->value = $this->value->getBuild()->getQuery($this->value);
		} else {
			$this->value = array_filter($this->format($this->value));
			if (empty($this->value)) {
				return '';
			}
			$this->value = implode(',', $this->value);
		}
		return '`' . $this->column . '` in(' . $this->value . ')';
	}

}
