<?php


namespace validator;


use Exception;

/**
 * Class RoundValidator
 * @package validator
 */
class RoundValidator extends BaseValidator
{


	public ?int $value = null;


	/**
	 * @return bool
	 * @throws Exception
	 */
	public function trigger(): bool
	{
		$value = $this->model->getAttribute($this->field);
		if ($value == null || round($value, $this->value) != $value) {
			return $this->addError('The param :attribute length error');
		}
		return true;
	}


}
