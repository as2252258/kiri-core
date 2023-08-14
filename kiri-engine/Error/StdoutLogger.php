<?php

declare(strict_types=1);

namespace Kiri\Error;

use Psr\Log\LoggerInterface;
use ReflectionException;

class StdoutLogger
{


	/**
	 * @var array
	 */
	private array $errors = [];


    /**
     * @param $message
     * @param string $model
     * @return bool
     * @throws ReflectionException
     */
	public function failure($message, string $model = 'app'): bool
	{
		if ($message instanceof \Exception) {
			$this->errors[$model] = $message->getMessage();
		} else {
			$this->errors[$model] = $message;
        }
        $logger = \Kiri::getDi()->get(LoggerInterface::class);
        $logger->error(throwable($message), []);
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
