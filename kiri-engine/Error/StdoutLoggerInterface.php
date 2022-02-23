<?php

namespace Kiri\Error;

use Psr\Log\LoggerInterface;

interface StdoutLoggerInterface extends LoggerInterface
{


	/**
	 * @param $message
	 * @param string $model
	 * @return bool
	 */
	public function addError($message, string $model = 'app'): bool;


	/**
	 * @param string $model
	 * @return mixed
	 */
	public function getLastError(string $model = 'app'): mixed;


}
