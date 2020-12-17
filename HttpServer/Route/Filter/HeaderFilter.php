<?php
declare(strict_types=1);


namespace HttpServer\Route\Filter;

use Exception;

/**
 * Class HeaderFilter
 * @package Snowflake\Snowflake\Route\Filter
 */
class HeaderFilter extends Filter
{


	/**
	 * @return bool
	 * @throws Exception
	 */
	public function check(): bool
	{
		return $this->validator();
	}

}
