<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/25 0025
 * Time: 10:14
 */
declare(strict_types=1);
namespace HttpServer\Exception;


use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 * Class AuthException
 * @package Snowflake\Snowflake\Exception
 */
class AuthException extends \Exception
{


	/**
	 * AuthException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	#[Pure] public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 7000, $previous);
	}
	
}
