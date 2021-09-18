<?php

namespace Server\Service;


use Exception;
use Http\Context\Context;
use Http\Exception\RequestException;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Dispatcher;
use Http\Handler\Handler;
use Http\Handler\TestRequest;
use Http\Message\ServerRequest;
use Http\Message\Stream;
use Http\Route\MiddlewareManager;
use Http\Route\Node;
use Kiri\Core\Help;
use Psr\Http\Message\ServerRequestInterface;
use Server\Constant;
use Server\Constrict\Request as ScRequest;
use Server\Constrict\RequestInterface;
use Server\Constrict\ResponseInterface;
use Server\Events\OnAfterRequest;
use Server\SInterface\OnClose;
use Server\SInterface\OnConnect;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

/**
 *
 */
class Http extends \Server\Abstracts\Http implements OnClose, OnConnect
{

	public TestRequest $request;


	public function init()
	{

		$this->request = new TestRequest();

		parent::init();
	}


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
			$PsrResponse = \response()->withStatus($throwable->getCode())
				->withContentType(\Http\Message\Response::CONTENT_TYPE_HTML)
				->withBody(new Stream(jTraceEx($throwable, null, true)));
		} finally {
			$this->response->sender($response, $PsrResponse);
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
