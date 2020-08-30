<?php


namespace HttpServer\Route\Filter;


use Throwable;

class FilterException extends \Exception
{

	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}
