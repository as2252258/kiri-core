<?php

namespace Http\Handler;

use Exception;
use Http\Context\Context;
use Http\Handler\Abstracts\HandlerManager;
use Http\Message\ServerRequest;
use Http\Message\Stream;
use Http\Route\MiddlewareManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Server\Constrict\RequestInterface;
use Server\Constrict\ResponseEmitter;
use Swoole\Http\Request;
use Swoole\Http\Response;

class TestRequest
{


	private ?ResponseEmitter $response = null;


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response): void
	{
		try {
			[$PsrRequest, $PsrResponse] = $this->initRequestResponse($request);

			/** @var Handler $handler */
			$handler = HandlerManager::get($request->server['request_uri'], $request->getMethod());
			if (is_null($handler)) {
				$PsrResponse->withStatus(404)->withBody(null);
			} else if (is_integer($handler)) {
				$PsrResponse->withStatus($handler)->withBody(null);
			} else {
				$middlewares = MiddlewareManager::get($handler->callback[0]::class, $handler->callback[1]);

				$stream = new Stream((new Dispatcher($handler, $middlewares))->handle($PsrRequest));

				$PsrResponse->withStatus(200)->withBody($stream);
			}
		} catch (\Throwable $throwable) {

		} finally {
			$this->response->sender($response, $PsrResponse);
		}
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		$PsrResponse = Context::setContext(\Server\Constrict\ResponseInterface::class, new \Http\Message\Response());

		$PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

		return [$PsrRequest, $PsrResponse];
	}

}
