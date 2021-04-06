<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\BaseObject;
use validator\Validator;

/**
 * Class HttpFilter
 * @package HttpServer\Http
 */
class HttpFilter extends BaseObject
{


	private array $hash = [];


	/**
	 * @param string $className
	 * @param string $method
	 * @param array $rules
	 * @return $this
	 */
	public function register(string $className, string $method, array $rules): HttpFilter
	{
		$this->hash[$className . '::' . $method] = $rules;
		return $this;
	}


	/**
	 * @param string $className
	 * @param string $method
	 * @return bool|Validator
	 * @throws Exception
	 */
	public function check(string $className, string $method): bool|Validator
	{
		if (!isset($this->hash[$className . '::' . $method])) {
			return true;
		}

		$rules = $this->hash[$className . '::' . $method][request()->getMethod()] ?? [];
		if (empty($rules)) {
			return true;
		}

		$validator = Validator::getInstance();
		$validator->setParams(Input()->load());
		foreach ($rules as $Key => $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validator->make($field, $val);
		}
		return $validator;
	}

}
