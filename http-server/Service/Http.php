<?php

namespace Server\Service;


use Exception;
use Http\Exception\RequestException;
use Http\Route\Node;
use Kiri\Core\Help;
use Server\Constant;
use Server\Events\OnAfterRequest;
use Protocol\Message\Response as MsgResponse;
use Server\Constrict\RequestInterface;
use Server\Constrict\ResponseInterface;
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
		try {
			[$request, $psr7Response] = \Server\Constrict\Request::create($request);
			/** @var RequestInterface $request */
			$node = $this->router->Branch_search($request);
			if (!($node instanceof Node)) {
				throw new RequestException(Constant::STATUS_404_MESSAGE, 404);
			}
			$psr7Response = $node->dispatch($request);
			if (!($psr7Response instanceof ResponseInterface)) {
				$psr7Response = $this->transferToResponse($psr7Response);
			}
		} catch (Error | \Throwable $exception) {
			$psr7Response = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			if (!isset($psr7Response)) {
				return;
			}
			$this->responseEmitter->sender($response, $psr7Response);
			$this->eventDispatch->dispatch(new OnAfterRequest());
		}
	}


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
		$responseData = $interface->_toArray($responseData);
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
