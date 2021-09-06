<?php

namespace Server\Service;


use Exception;
use Http\Exception\RequestException;
use Http\Route\Node;
use Kiri\Core\Help;
use Server\Events\OnAfterRequest;
use Server\Message\Response as MsgResponse;
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
			$request = \Server\Constrict\Request::create($request);
			$node = $this->router->Branch_search($request);
//			if (!($node instanceof Node)) {
//				throw new RequestException('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', 404);
//			}
//			if (!(($responseData = $node->dispatch($request)) instanceof ResponseInterface)) {
//				$responseData = $this->transferToResponse($responseData);
//			}
			$responseData = $this->transferToResponse('hello word.');
		} catch (Error | \Throwable $exception) {
			$responseData = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			$this->responseEmitter->sender($response, $responseData);
			$this->eventDispatch->dispatch(new OnAfterRequest());
		}
	}


	/**
	 * @param mixed $responseData
	 * @return ResponseInterface
	 * @throws Exception
	 */
	private function transferToResponse(mixed $responseData): mixed
	{
		$interface = $this->response->withStatus(200);
		if (!$interface->hasContentType()) {
			$interface->withContentType(MsgResponse::CONTENT_TYPE_JSON);
		}
		$responseData = $interface->_toArray($responseData);
		if ($interface->getContentType() == MsgResponse::CONTENT_TYPE_XML) {
			$interface->stream->write(Help::toXml($responseData));
		} else if (is_array($responseData)) {
			$interface->stream->write(json_encode($responseData));
		} else {
			$interface->stream->write((string)$responseData);
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
