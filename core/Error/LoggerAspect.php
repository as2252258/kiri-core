<?php


namespace Kiri\Error;


use Kiri\IAspect;


/**
 * Class LoggerAspect
 * @package Kiri\Error
 */
class LoggerAspect implements IAspect
{

	private string $className = '';
	private string $methodName = '';


	/**
	 * @param mixed $handler
	 * @param array $params
	 * @return mixed
	 */
	public function invoke(mixed $handler, array $params = []): mixed
	{
		$startTime = microtime(true);

		$data = call_user_func($handler, ...$params);

		$this->print_runtime($handler, $startTime);

		return $data;
	}


	/**
	 * @param $handler
	 * @param $startTime
	 */
	private function print_runtime($handler, $startTime)
	{
		$className = $handler[0]::class;
		$methodName = $handler[1];

		$runTime = round(microtime(true) - $startTime, 6);
		echo sprintf('run %s::%s use time %6f', $className, $methodName, $runTime);
		echo PHP_EOL;
	}


	public function before(): void
	{
		// TODO: Implement before() method.
	}


	public function after(mixed $response): void
	{
		// TODO: Implement after() method.
	}
}
