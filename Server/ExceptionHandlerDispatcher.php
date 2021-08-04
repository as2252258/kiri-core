<?php

namespace Server;


use Server\Constrict\Response;
use Server\Constrict\Response as CResponse;

/**
 *
 */
class ExceptionHandlerDispatcher implements ExceptionHandlerInterface
{


	/**
	 * @param \Throwable $exception
	 * @param CResponse $response
	 * @return Response
	 */
	public function emit(\Throwable $exception, Response $response): Response
	{
		$code = $exception->getCode() == 0 ? 500 : $exception->getCode();
		$data = $code == 404 ? $exception->getMessage() : jTraceEx($exception, null, true);
		return $response->setContent($data)
			->setFormat(CResponse::HTML)
			->setStatusCode($code);
	}

}
