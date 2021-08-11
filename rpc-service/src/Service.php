<?php

namespace Rpc;


use Annotation\Inject;
use Exception;
use HttpServer\Exception\RequestException;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
use Kiri\Abstracts\Config;
use Kiri\Events\EventDispatch;
use Kiri\Events\EventProvider;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use Server\Constant;
use Server\Constrict\Response;
use Server\Constrict\ResponseEmitter;
use Server\Events\OnAfterRequest;
use Server\ExceptionHandlerDispatcher;
use Server\ExceptionHandlerInterface;
use Server\RequestInterface;
use Server\ResponseInterface;
use Swoole\Server;
use Server\Constrict\Request as cRequest;
use function Swoole\Coroutine\defer;


/**
 * Class Service
 * @package Rpc
 */
class Service extends \Server\Abstracts\Server
{

	const A_DEFAULT = [
		'Source'       => '',
		'Package'      => '',
		'Path'         => '',
		'Content-Type' => '',
		'Method'       => ''
	];

	private Router $router;


	/** @var EventProvider */
	#[Inject(EventProvider::class)]
	public EventProvider $eventProvider;


	public Response $response;


	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	#[Inject(ResponseEmitter::class)]
	public ResponseEmitter $responseEmitter;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->router = Kiri::getApp('router');

		$exceptionHandler = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Kiri::getDi()->get($exceptionHandler);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactorId
	 * @throws Exception
	 */
	public function onConnect(Server $server, int $fd, int $reactorId)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::CONNECT, null, [$server, $fd, $reactorId]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * on tcp client close
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::CLOSE, null, [$server, $fd]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * on tcp client close
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));

		$this->runEvent(Constant::DISCONNECT, null, [$server, $fd]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reID
	 * @param string $data
	 * @throws Exception
	 */
	public function onReceive(Server $server, int $fd, int $reID, string $data)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));
		try {
			$node = $this->router->Branch_search($this->requestSpl($data, $fd));
			if (!($node instanceof Node)) {
				throw new RequestException('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', 404);
			}
			$this->response->setClientId($fd, $reID);
			if (!(($responseData = $node->dispatch()) instanceof ResponseInterface)) {
				$responseData = $this->response->setContent($responseData)->setStatusCode(200);
			}
		} catch (\Throwable $exception) {
			$responseData = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			$this->responseEmitter->sender($server, $responseData);
		}
	}


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $client
	 * @throws Exception
	 */
	public function onPacket(Server $server, string $data, array $client)
	{
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));
		try {
			$request = $this->requestSpl((int)$client['server_port'], $data);

			$result = $this->router->find_path($request)?->dispatch();

			$server->sendto($client['address'], $client['port'], $result);
		} catch (\Throwable $exception) {
			$server->sendto($client['address'], $client['port'], $exception->getMessage());
		}
	}


	/**
	 * @param string $data
	 * @param int $fd
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 * @throws Exception
	 */
	public function requestSpl(string $data, int $fd = 0): RequestInterface
	{
		$data = Protocol::parse($data);
		$sRequest = new Request();
		$sRequest->setClientId($fd);
		$sRequest->setPosts($data->getData());
		$sRequest->setHeaders(array_merge($data->getHeaders(), [
			'request_uri'    => $data->parseUrl(),
			'request_method' => $data->getHeaders()['Method']
		]));
		Context::setContext(Request::class, $sRequest);
		return di(cRequest::class);
	}

}
