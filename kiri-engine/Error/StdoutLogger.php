<?php

declare(strict_types=1);

namespace Kiri\Error;

use Kiri\Abstracts\Logger;

class StdoutLogger extends Logger
{


	/**
	 * @var array
	 */
	private array $errors = [];


	/**
	 * @param $message
	 * @param string $model
	 * @return bool
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
