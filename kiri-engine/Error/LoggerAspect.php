<?php


namespace Kiri\Error;


use Exception;
use Http\Aspect\OnAspectInterface;
use Http\Aspect\OnJoinPointInterface;
use Http\Constrict\RequestInterface;
use Kiri\Kiri;


/**
 * Class LoggerAspect
 * @package Kiri\Error
 */
class LoggerAspect implements OnAspectInterface
{


	/**
	 * @param OnJoinPointInterface $joinPoint
	 * @return mixed
	 * @throws Exception
	 */
	public function process(OnJoinPointInterface $joinPoint): mixed
	{
		$time = microtime(true);

		$response = $joinPoint->process();

		$this->print_runtime($time);

		return $response;
	}


	/**
	 * @param $startTime
	 * @throws Exception
	 */
	private function print_runtime($startTime)
	{
		$request = Kiri::getDi()->get(RequestInterface::class);

		$runTime = round(microtime(true) - $startTime, 6);
		echo sprintf('run %s use time %6f', $request->getUri()->__toString(), $runTime);
		echo PHP_EOL;
	}

}
