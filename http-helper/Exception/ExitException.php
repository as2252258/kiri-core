<?php
declare(strict_types=1);

namespace Http\Exception;



use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 * Class ExitException
 * @package Http\Exception
 */
class ExitException extends \Exception
{

	/**
	 * ExitException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	#[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}
