<?php

namespace Server;


use Server\Constrict\Response;
use Server\Constrict\Response as CResponse;
use Throwable;

/**
 *
 */
class ExceptionHandlerDispatcher implements ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param CResponse $response
	 * @return CResponse|\HttpServer\Http\Response
	 */
	public function emit(Throwable $exception, Response $response): Response|\HttpServer\Http\Response
	{
		if ($exception->getCode() == 404) {
			return $response->setContent($exception->getMessage())
				->setFormat(CResponse::HTML)
				->setStatusCode(404);
		}
		$code = $exception->getCode() == 0 ? 500 : $exception->getCode();
		return $response->setContent(jTraceEx($exception, null, true))
			->setFormat(CResponse::HTML)
			->setStatusCode($code);
	}

}
