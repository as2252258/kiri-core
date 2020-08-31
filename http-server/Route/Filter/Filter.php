<?php


namespace HttpServer\Route\Filter;


use Exception;
use HttpServer\Application;
use validator\Validator;

/**
 * Class Filter
 * @package Snowflake\Snowflake\Route\Filter
 */
abstract class Filter extends Application
{

	public $rules = [];

	public $params = [];

	abstract public function check();


	/**
	 * @return bool
	 * @throws Exception
	 */
	protected function validator()
	{
		$validator = Validator::getInstance();
		$validator->setParams($this->params);
		foreach ($this->rules as $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validator->make($field, $val);
		}
		if (!$validator->validation()) {
			return $this->addError($validator->getError());
		}
		return true;
	}

}
