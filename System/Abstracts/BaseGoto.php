<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;
use HttpServer\Exception\ExitException;
use Snowflake\Core\Json;

/**
 * Class BaseGoto
 * @package Snowflake\Abstracts
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
