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
//
//
//
//	/**
//	 * @param Request $request
//	 * @param Response $response
//	 * @throws Exception
//	 */
//	public function onRequest(Request $request, Response $response): void
//	{
//		$this->request->onRequest($request, $response);
//		return;
//
//		try {
//			if (!(($node = $this->router->radix_tree($Psr7Request = ScRequest::create($request))) instanceof Node)) {
//				throw new RequestException(Constant::STATUS_404_MESSAGE, 404);
//			}
//			if (!(($psr7Response = $node->dispatch($Psr7Request)) instanceof ResponseInterface)) {
//				$psr7Response = $this->transferToResponse($psr7Response);
//			}
//			$psr7Response->withHeader('Run-Time', $this->_runTime($request));
//		} catch (Error | \Throwable $exception) {
//			$psr7Response = $this->exceptionHandler->emit($exception, $this->response);
//		} finally {
//			$this->responseEmitter->sender($response, $psr7Response);
//			$this->eventDispatch->dispatch(new OnAfterRequest());
//		}
//	}



	/**
	 * @param mixed $responseData
	 * @return ResponseInterface
	 * @throws Exception
	 */
	private function transferToResponse(mixed $responseData): ResponseInterface
	{
		$interface = $this->response->withStatus(200);
		if (!$interface->hasContentType()) {
			$interface->withContentType('application/json;charset=utf-8');
		}
		if (is_object($responseData)) {
			$responseData = get_object_vars($responseData);
		}
		if ($interface->getContentType() == 'application/xml;charset=utf-8') {
			$interface->getBody()->write(Help::toXml($responseData));
		} else if (is_array($responseData)) {
			$interface->getBody()->write(json_encode($responseData));
		} else {
			$interface->getBody()->write((string)$responseData);
		}
		return $interface;
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
