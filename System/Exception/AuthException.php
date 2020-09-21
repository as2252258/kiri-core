<?php


namespace Snowflake\Exception;


use Throwable;

class AuthException extends \Exception
{

	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, 401, $previous);
	}

}
