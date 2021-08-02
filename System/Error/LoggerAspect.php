<?php


namespace Snowflake\Error;


use Snowflake\IAspect;


/**
 * Class LoggerAspect
 * @package Snowflake\Error
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
		$className = $handler::class;
		$methodName = $handler;

		$runTime = round(microtime(true) - $startTime, 6);
		echo sprintf('run %s::%s use time %6f', $className, $methodName, $runTime);
		echo PHP_EOL;
	}


}
