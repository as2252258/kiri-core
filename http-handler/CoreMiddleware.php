<?php

namespace Http\Handler;

use Http\Handler\Abstracts\Middleware;
use Http\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CoreMiddleware extends Middleware
{


	/**
	 * @param ServerRequest $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$this->response->withAccessControlAllowOrigin('*')
			->withAccessControlRequestMethod($request->getAccessControlRequestMethod())
			->withAccessControlAllowHeaders($request->getAccessControlAllowHeaders());

		var_dump($this->response);

		return $handler->handle($request);
	}

}
