<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/3 0003
 * Time: 15:28
 */
declare(strict_types=1);

namespace validator;


/**
 * Class ArrayValidator
 * @package validator
 */
class ArrayValidator extends BaseValidator
{

	/**
	 * @return bool
	 *
	 * 检查
	 */
	public function trigger(): bool
	{
		$param = $this->getParams();
		if (empty($param) || !is_array($param)) {
			return true;
		}
		if (!isset($param[$this->field])) {
			return true;
		}
		if (!is_array($param[$this->field])) {
			return $this->addError("The param :attribute must a array");
		}
		return true;
	}

	/**
	 * @param $data
	 * @return array
	 *
	 * 转成数组
	 */
	private function toArray($data): array
	{
		if (is_numeric($data)) {
			return [];
		} else if (is_null(json_decode($data, true))) {
			return [];
		} elseif (is_object($data)) {
			$data = get_object_vars($data);
		}

		$_tmp = [];
		foreach ($data as $key => $val) {
			if (is_object($val)) {
				$_tmp[$key] = $this->toArray($val);
			} else if (is_array($val)) {
				$_tmp[$key] = $this->toArray($val);
			} else {
				$_tmp[$key] = $val;
			}
		}

		return $_tmp;
	}

}
