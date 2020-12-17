<?php
declare(strict_types=1);


namespace HttpServer\Route\Filter;



use Throwable;

class FilterException extends \Exception
{


	/**
	 * FilterException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}
