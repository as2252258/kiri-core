<?php

namespace Server\Service;


use Exception;
use Http\Exception\RequestException;
use Http\Route\Node;
use Server\Events\OnAfterRequest;
use Server\ResponseInterface;
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
	 */
	public function onRequest(Request $request, Response $response): void
	{
		// TODO: Implement onRequest() method.
		try {
			$node = $this->router->Branch_search(\Server\Constrict\Request::create($request));
			if (!($node instanceof Node)) {
				throw new RequestException('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', 404);
			}
			if (!(($responseData = $node->dispatch()) instanceof ResponseInterface)) {
				$responseData = $this->response->setContent($responseData)->setStatusCode(200);
			}
		} catch (Error | \Throwable $exception) {
			$responseData = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			$this->responseEmitter->sender($response, $responseData);
			$this->eventDispatch->dispatch(new OnAfterRequest());
		}
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
