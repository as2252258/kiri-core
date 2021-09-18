<?php

namespace Http\Handler;

use Exception;
use Kiri\Core\Help;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 *
 */
class Dispatcher extends \Http\Handler\Abstracts\Handler
{


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->execute($request);
	}
}
