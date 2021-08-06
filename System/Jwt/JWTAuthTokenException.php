<?php

namespace Snowflake\Jwt;


use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 *
 */
class JWTAuthTokenException extends \Exception
{


	/**
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	#[Pure] public function __construct($message = "", $code = 4001, Throwable $previous = null)
	{
		parent::__construct($message, 4001, $previous);
	}


}
