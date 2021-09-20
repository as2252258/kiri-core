<?php

namespace Server\Service;


use Annotation\Inject;
use Exception;
use Http\Handler\Context;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Dispatcher;
use Http\Handler\Handler;
use Http\Message\ContentType;
use Http\Message\ServerRequest;
use Http\Message\Stream;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Kiri;
use Psr\Http\Message\ServerRequestInterface;
use Server\Constrict\RequestInterface;
use Server\Constrict\ResponseInterface;
use Server\SInterface\OnClose;
use Server\SInterface\OnConnect;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

/**
 *
 */
class Http extends \Server\Abstracts\Http implements OnClose, OnConnect
{


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd): void
	{
		// TODO: Implement onConnect() method.
	}


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
			if (is_integer($handler)) {
				$PsrResponse->withStatus($handler)->withBody(new Stream('Allow Method[' . $request->getMethod() . '].'));
			} else if (is_null($handler)) {
				$PsrResponse->withStatus(404)->withBody(new Stream('Page not found.'));
			} else {
				$PsrResponse = $this->handler($handler, $PsrRequest);
			}
		} catch (\Throwable $throwable) {
            $PsrResponse= $this->exceptionHandler->emit($throwable, $this->response);
		} finally {
			$this->responseEmitter->sender($response, $PsrResponse);
		}
	}


	/**
	 * @param Handler $handler
	 * @param $PsrRequest
	 * @return ResponseInterface
	 * @throws Exception
	 */
	protected function handler(Handler $handler, $PsrRequest): \Psr\Http\Message\ResponseInterface
	{
		$middlewares = MiddlewareManager::get($handler->callback);

		$dispatcher = new Dispatcher($handler, $middlewares);

		return $dispatcher->handle($PsrRequest);
	}


	/**
	 * @param Request $request
	 * @return array<ServerRequestInterface, ResponseInterface>
	 * @throws Exception
	 */
	private function initRequestResponse(Request $request): array
	{
		$PsrResponse = Context::setContext(ResponseInterface::class, new \Http\Message\Response());

		$PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

		return [$PsrRequest, $PsrResponse];
	}



	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd): void
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd): void
	{
	}

}
