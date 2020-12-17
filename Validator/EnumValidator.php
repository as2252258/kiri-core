<?php

declare(strict_types=1);

namespace validator;



/**
 * Class EnumValidator
 * @package validator
 */
class EnumValidator extends BaseValidator
{

	public array $value = [];

	/**
	 * @return bool
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return $this->addError('The param :attribute is null');
		}
		$value = $param[$this->field];
		if (is_null($value)) {
			return $this->addError('The param :attribute is null');
		}

		if (!is_array($this->value)) {
			return true;
		}
		if (!in_array($value, $this->value)) {
			return $this->addError($this->i());
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function i(): string
	{
		return 'The param :attribute value only in ' . implode(',', $this->value);
	}

}
