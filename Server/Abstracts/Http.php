<?php

namespace Server\Abstracts;

use Annotation\Inject;
use HttpServer\Route\Router;
use Kiri\Abstracts\Config;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constrict\Response as CResponse;
use Server\Constrict\ResponseEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\ListenerHelper;
use Server\SInterface\OnRequest;


/**
 *
 */
abstract class Http implements OnRequest
{


	use EventDispatchHelper;
	use ResponseHelper;

	/** @var Router|mixed */
	#[Inject('router')]
	public Router $router;


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
		$exceptionHandler = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
	}

}
