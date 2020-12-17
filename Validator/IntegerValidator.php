<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 18:44
 */
declare(strict_types=1);

namespace validator;


class IntegerValidator extends BaseValidator
{

	const MIN = 'min';
	const MAX = 'max';

	public ?int $value = null;
	private string $type = '';

	/**
	 * @return bool
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return true;
		}

		$value = $param[$this->field] ?? null;
		if ($value === null) {
			return $this->addError('The :attribute can not is null.');
		}
		if ($this->type !== self::MIN && $value < $this->value) {
			return $this->addError('The ' . $this->field . ' cannot be less than the default value.');
		}

		if ($this->type !== self::MAX && $value > $this->value) {
			return $this->addError('The ' . $this->field . ' cannot be greater than the default value.');
		}
		return true;
	}
}
