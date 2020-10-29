<?php
declare(strict_types=1);


namespace Snowflake\Exception;


use Throwable;

/**
 * Class AuthException
 * @package Snowflake\Exception
 */
class AuthException extends \Exception
{

	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, 401, $previous);
	}

}
