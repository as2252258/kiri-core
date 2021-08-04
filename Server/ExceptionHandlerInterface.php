<?php

namespace Server;

use HttpServer\Http\Response as SResponse;
use Server\Constrict\Response;
use Throwable;

/**
 *
 */
interface ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param Response $response
	 * @return Response|SResponse
	 */
	public function emit(Throwable $exception, Response $response): Response|SResponse;

}
