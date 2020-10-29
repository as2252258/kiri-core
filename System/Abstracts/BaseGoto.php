<?php
declare(strict_types=1);

namespace Snowflake\Abstracts;


use Exception;
use HttpServer\Exception\ExitException;
use Snowflake\Core\JSON;

/**
 * Class BaseGoto
 * @package Snowflake\Abstracts
 */
class BaseGoto extends Component
{

	/**
	 * @param $message
	 * @param int $statusCode
	 * @return mixed|void
	 * @throws Exception
	 */
	public function end(string $message, $statusCode = 200)
	{
		throw new ExitException(JSON::to(12350, $message), $statusCode);
	}

}
