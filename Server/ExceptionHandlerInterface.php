<?php

namespace Server;

use Server\Constrict\Response;

/**
 *
 */
interface ExceptionHandlerInterface
{


	/**
	 * @param \Throwable $exception
	 * @param Response $response
	 * @return Response
	 */
	public function emit(\Throwable $exception, Response $response): Response;

}
