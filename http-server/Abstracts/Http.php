<?php

namespace Server\Abstracts;

use Annotation\Inject;
use Http\Handler\Router;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Server\Constrict\ResponseEmitter;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\SInterface\OnRequest;


/**
 *
 */
abstract class Http extends Server implements OnRequest
{


	use EventDispatchHelper;
	use ResponseHelper;

	/** @var Router|mixed */
	#[Inject(Router::class)]
	public Router $router;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws ConfigException
	 */
	public function init()
	{
		$exceptionHandler = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
		$this->responseEmitter = Kiri::getDi()->get(ResponseEmitter::class);
	}

}
