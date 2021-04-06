<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/4 0004
 * Time: 18:44
 */
declare(strict_types=1);

namespace validator;


class TypesOfValidator extends BaseValidator
{


	const JSON = 'json';
	const FLOAT = 'float';
	const ARRAY = 'array';
	const STRING = 'string';
	const INTEGER = 'integer';
	const SERIALIZE = 'serialize';

	private ?int $min = null;
	private ?int $max = null;

	/** @var array */
	public array $types = [
		self::JSON      => 'json',
		self::FLOAT     => 'float',
		self::ARRAY     => 'array',
		self::STRING    => 'string',
		self::INTEGER   => 'integer',
		self::SERIALIZE => 'serialize',
	];

	/** @var string */
	public string $method;


	/**
	 * @return bool
	 */
	public function trigger(): bool
	{
		if (!in_array($this->method, $this->types)) {
			return true;
		}

		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return true;
		}

		$value = $param[$this->field];

		$method = $this->method . 'Format';

		if ($value === null) {
			return $this->addError('This ' . $this->field . ' is not an empty data.');
		}

		return $this->{$method}($value);
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function jsonFormat($value): bool
	{
		if (!is_string($value) || is_numeric($value)) {
			return $this->addError('The ' . $this->field . ' not is JSON data.');
		}
		if (is_null(json_decode($value))) {
			return $this->addError('The ' . $this->field . ' not is JSON data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function serializeFormat($value): bool
	{
		if (!is_string($value) || is_numeric($value)) {
			return $this->addError('The ' . $this->field . ' not is serialize data.');
		}
		if (false === swoole_unserialize($value)) {
			return $this->addError('The ' . $this->field . ' not is serialize data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function arrayFormat($value): bool
	{
		if (!is_array($value)) {
			return $this->addError('The ' . $this->field . ' not is array data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function stringFormat($value): bool
	{
		if (is_array($value) || is_object($value) || is_bool($value)) {
			return $this->addError('The ' . $this->field . ' not is string data.');
		}
		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function integerFormat($value): bool
	{
		if (!is_numeric($value)) {
			return $this->addError('The ' . $this->field . ' not is number data.');
		}
		if ((int)$value != $value) {
			return $this->addError('The ' . $this->field . ' not is number data.');
		}

		return true;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	public function floatFormat($value): bool
	{
		$trim = (float)$value;
		if ($trim != $value || !is_float($trim)) {
			return $this->addError('The ' . $this->field . ' not is float data.');
		}
		return true;
	}

}
