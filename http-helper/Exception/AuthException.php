<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/25 0025
 * Time: 10:14
 */
declare(strict_types=1);
namespace Http\Exception;



use Throwable;

/**
 * Class AuthException
 * @package Kiri\Kiri\Exception
 */
class AuthException extends \Exception
{


	/**
	 * AuthException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 7000, $previous);
	}
	
}