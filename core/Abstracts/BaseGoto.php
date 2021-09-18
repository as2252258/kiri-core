<?php
declare(strict_types=1);

namespace Kiri\Abstracts;


use Exception;
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
	 * @throws Exception
	 */
	public function end(string $message, int $statusCode = 200): mixed
	{
		throw new Exception(Json::to(12350, $message), $statusCode);
	}

}
