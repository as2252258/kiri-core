<?php

namespace Server;

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
	 * @return ResponseInterface
	 */
	public function emit(Throwable $exception, Response $response): ResponseInterface;

}
