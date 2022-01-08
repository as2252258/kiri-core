<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:34
 */
declare(strict_types=1);

namespace Kiri\Exception;



use Throwable;

/**
 * Class ComponentException
 * @package Kiri\Kiri\Exception
 */
class ComponentException extends \Exception
{


	/**
	 * ComponentException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL)
	{
		parent::__construct($message, 5000, $previous);
	}

}
