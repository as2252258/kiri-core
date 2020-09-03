<?php


namespace HttpServer\Route\Filter;


use Exception;

/**
 * Class QueryFilter
 * @package Snowflake\Snowflake\Route\Filter
 */
class QueryFilter extends Filter
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
