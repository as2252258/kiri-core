<?php
declare(strict_types=1);


namespace HttpServer\Route\Filter;


use JetBrains\PhpStorm\Pure;
use Throwable;

class FilterException extends \Exception
{


	/**
	 * FilterException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	#[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}
