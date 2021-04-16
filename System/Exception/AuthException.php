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

	/**
	 * AuthException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, 4001, $previous);
	}

}
