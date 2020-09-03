<?php


namespace HttpServer\Route\Filter;


use Exception;

/**
 * Class BodyFilter
 * @package Snowflake\Snowflake\Route\Filter
 */
class BodyFilter extends Filter
{

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function check()
	{
		return $this->validator();
	}

}
