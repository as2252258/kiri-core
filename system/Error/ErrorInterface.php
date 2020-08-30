<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 10:25
 */

namespace Snowflake\Error;

/**
 * Interface ErrorInterface
 * @package BeReborn\Error
 */
interface ErrorInterface
{

	/**
	 * @param $message
	 * @param $file
	 * @param $line
	 * @param int $code
	 * @return mixed
	 */
	public function sendError($message, $file, $line, $code = 500);

}
