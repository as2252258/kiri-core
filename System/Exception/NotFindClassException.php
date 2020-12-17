<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:32
 */
declare(strict_types=1);

namespace Snowflake\Exception;



use Throwable;

/**
 * Class NotFindClassException
 * @package Snowflake\Snowflake\Exception
 */
class NotFindClassException extends \Exception
{

	/**
	 * NotFindClassException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
	{
		$message = "No class named `$message` was found, please check if the class name is correct";
		parent::__construct($message, 404, $previous);
	}

}
