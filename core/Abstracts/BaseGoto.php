<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
use Http\Exception\ExitException;
use Kiri\Core\Json;

/**
 * Class BaseGoto
 * @package Kiri\Abstracts
 */
class BaseGoto extends Component
{

	/**
	 * @param string $message
	 * @param int $statusCode
	 * @return mixed
	 * @throws ExitException
	 */
	public function end(string $message, $statusCode = 200): mixed
	{
		throw new ExitException(Json::to(12350, $message), $statusCode);
	}

}
