<?php


namespace Snowflake\Abstracts;


use Exception;
use HttpServer\Exception\ExitException;

/**
 * Class BaseGoto
 * @package Snowflake\Abstracts
 */
class BaseGoto extends Component
{

	/**
	 * @param $message
	 * @param int $statusCode
	 * @throws Exception
	 */
	public function end($message, $statusCode = 200)
	{
		throw new ExitException($message, $statusCode);
	}

}
