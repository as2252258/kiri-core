<?php


namespace Kiri\Error;


use Exception;
use Kiri\Message\Aspect\OnAspectInterface;
use Kiri\Message\Aspect\OnJoinPointInterface;
use Kiri\Message\Constrict\RequestInterface;
use Kiri;
use Psr\Log\LoggerInterface;


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

		$logger = Kiri::getDi()->get(LoggerInterface::class);
		$logger->debug(sprintf('run %s use time %6f', $request->getUri()->__toString(), $runTime));
	}

}
