<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 10:25
 */
declare(strict_types=1);

namespace Kiri\Error;

/**
 * Interface ErrorInterface
 * @package Kiri\Kiri\Error
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
	public function sendError($message, $file, $line, $code = 500): mixed;

}
