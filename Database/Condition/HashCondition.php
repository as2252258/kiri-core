<?php
declare(strict_types=1);
namespace Database\Condition;

/**
 * Class HashCondition
 * @package Yoc\db\condition
 */
class HashCondition extends Condition
{

	/**
	 * @return string
	 */
	public function builder()
	{
		$array = [];
		if (empty($this->value)) {
			return '';
		}
		foreach ($this->value as $key => $value) {
			if ($value === null) {
				continue;
			}
			$array[] = $this->resolve($key, $value);
		}
		return implode(' AND ', $array);
	}

}
