<?php

namespace Server;


use Protocol\Message\Stream;
use Server\Constrict\Response;
use Server\Constrict\ResponseInterface;
use Throwable;


/**
 *
 */
class ExceptionHandlerDispatcher implements ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param Response $response
	 * @return ResponseInterface
	 */
	public function emit(Throwable $exception, Response $response): ResponseInterface
	{
		$response->withContentType('text/html;charset=utf-8');
		if ($exception->getCode() == 404) {
			return $response->withBody(new Stream($exception->getMessage()))
				->withStatus(404);
		}
		$code = $exception->getCode() == 0 ? 500 : $exception->getCode();
		return $response->withBody(new Stream(jTraceEx($exception, null, true)))
			->withStatus($code);
	}

}
