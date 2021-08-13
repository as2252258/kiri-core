<?php

namespace Server\Abstracts;

use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constrict\UdpEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnPacket;
use Server\SInterface\OnReceive;


/**
 *
 */
abstract class Udp extends Server implements OnPacket
{

	use EventDispatchHelper;
	use ResponseHelper;



	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$exceptionHandler = Config::get('exception.udp', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
		$this->responseEmitter = Kiri::getDi()->get(UdpEmitter::class);
	}

}