<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 17:04
 */
declare(strict_types=1);

namespace validator;


class LengthValidator extends BaseValidator
{

	const MAX_LENGTH = 'max';
	const MIN_LENGTH = 'min';

	public string $method;

	public int $value;

	/**
	 * @return bool
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			if ($this->method != self::MAX_LENGTH) {
				return $this->addError('The param :attribute not exists');
			} else {
				return TRUE;
			}
		}
		$value = $param[$this->field];
		if (is_null($value)) {
			return $this->addError('The param :attribute is null');
		}
		return match (strtolower($this->method)) {
			self::MAX_LENGTH => $this->maxLength($value),
			self::MIN_LENGTH => $this->minLength($value),
			default => $this->defaultLength($value),
		};
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否大于最大长度
	 */
	private function maxLength($value): bool
	{
		if (is_array($value)) {
			if (count($value) > $value) {
				return $this->addError('The param :attribute length overflow');
			}
		} else {
			if (is_numeric($value) && strlen((string)$value) > $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (strlen($value) > $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
		}
		return TRUE;
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否小于最小长度
	 */
	private function minLength($value): bool
	{
		if (is_array($value)) {
			if (count($value) < $value) {
				return $this->addError('The param :attribute length error');
			}
		} else {
			if (is_numeric($value) && strlen((string)$value) < $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (strlen($value) < $this->value) {
				return $this->addError('The param :attribute length error');
			}
		}
		return TRUE;
	}

	/**
	 * @param $value
	 * @return bool
	 *
	 * 效验长度是否小于最小长度
	 */
	private function defaultLength($value): bool
	{
		if (is_array($value)) {
			if (count($value) !== $value) {
				return $this->addError('The param :attribute length error');
			}
		} else {
			if (is_numeric($value) && strlen((string)$value) !== $this->value) {
				return $this->addError('The param :attribute length overflow');
			}
			if (mb_strlen($value) !== $this->value) {
				return $this->addError('The param :attribute length error; ' . mb_strlen($value) . ':' . $this->value);
			}
		}
		return TRUE;
	}
}
