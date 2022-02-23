<?php

namespace Kiri\Error;

use Kiri\Abstracts\Logger;
use Kiri\Exception\ConfigException;

class StdoutLogger extends Logger implements StdoutLoggerInterface
{


	private array $errors = [];


	/**
	 * @param $message
	 * @param string $model
	 * @return bool
	 * @throws ConfigException
	 */
	public function addError($message, string $model = 'app'): bool
	{
		$this->error($model, [$message]);
		if ($message instanceof \Exception) {
			$this->errors[$model] = $message->getMessage();
		} else {
			$this->errors[$model] = $message;
		}
		return false;
	}


	/**
	 * @param string $model
	 * @return mixed
	 */
	public function getLastError(string $model = 'app'): mixed
	{
		return $this->errors[$model] ?? 'Unknown error.';
	}

}
