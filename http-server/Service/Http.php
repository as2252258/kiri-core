<?php

namespace Server\Service;


use Exception;
use Http\Exception\RequestException;
use Http\Route\Node;
use Kiri\Core\Help;
use Server\Constant;
use Server\Constrict\Request as ScRequest;
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
			if (!(($node = $this->router->radix_tree($Psr7Request = ScRequest::create($request))) instanceof Node)) {
				throw new RequestException(Constant::STATUS_404_MESSAGE, 404);
			}
			if (!(($psr7Response = $node->dispatch($Psr7Request)) instanceof ResponseInterface)) {
				$psr7Response = $this->transferToResponse($psr7Response);
			}
			$psr7Response->withHeader('Run-Time', $this->_runTime($request));
		} catch (Error | \Throwable $exception) {
			$psr7Response = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			$this->responseEmitter->sender($response, $psr7Response);
			$this->eventDispatch->dispatch(new OnAfterRequest());
		}
	}


	/**
	 * @param Request $request
	 * @return float
	 */
	private function _runTime(Request $request): float
	{
		return round(microtime(true) - ($request->server['request_time_float'] - $request->server['request_time']), 6);
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
