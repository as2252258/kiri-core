<?php

namespace Http\Abstracts;

use Http\Constrict\Response;
use Throwable;
use Http\Constrict\ResponseInterface;

/**
 *
 */
interface ExceptionHandlerInterface
{


	/**
	 * @param Throwable $exception
	 * @param Response $response
	 * @return ResponseInterface
	 */
	public function emit(Throwable $exception, Response $response): ResponseInterface;

}
