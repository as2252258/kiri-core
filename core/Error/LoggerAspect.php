<?php


namespace Kiri\Error;


use Exception;
use Kiri\IAspect;


/**
 * Class LoggerAspect
 * @package Kiri\Error
 */
class LoggerAspect implements IAspect
{

	private float $time;


	/**
	 * @param mixed $handler
	 * @param array $params
	 * @return mixed
	 */
	public function invoke(mixed $handler, array $params = []): mixed
	{
		return call_user_func($handler, ...$params);
	}


	/**
	 * @param $startTime
	 * @throws Exception
	 */
	private function print_runtime($startTime)
	{
		$runTime = round(microtime(true) - $startTime, 6);
		echo sprintf('run %s use time %6f', request()->getUri()->__toString(), $runTime);
		echo PHP_EOL;
	}


	public function before(): void
	{
		// TODO: Implement before() method.
		$this->time = microtime(true);
	}


	/**
	 * @throws Exception
	 */
	public function after(mixed $response): void
	{
		// TODO: Implement after() method.
		$this->print_runtime($this->time);
	}
}
