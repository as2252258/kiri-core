<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/20 0020
 * Time: 17:32
 */
declare(strict_types=1);

namespace validator;


class EmailValidator extends BaseValidator
{
	
	/**
	 * @return bool
	 * 检查是否存在
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return true;
		} else {
			$value = $param[$this->field];
			if (preg_match('/^[a-zA-Z0-9]+([\.\_]{1,})[a-zA-Z0-9]+@[a-zA-Z]+(\.\w+)+/', $value)) {
				return true;
			} else {
				return $this->addError('The param :attribute format error');
			}
		}
	}
	
}
