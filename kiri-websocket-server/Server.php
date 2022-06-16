<?php

namespace Kiri\Websocket;

use Closure;
use Co\WaitGroup;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Inject;
use Kiri\Context;
use Kiri\Di\ContainerInterface;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;
use Kiri\Message\Abstracts\ExceptionHandlerInterface;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\ExceptionHandlerDispatcher;
use Kiri\Message\Handler\DataGrip;
use Kiri\Message\Handler\Dispatcher;
use Kiri\Message\Handler\Handler;
use Kiri\Message\Handler\RouterCollector;
use Kiri\Message\ResponseEmitter;
use Kiri\Message\ServerRequest;
use Kiri\Message\Constrict\Response as CResponse;
use Kiri\Server\Events\OnShutdown;
use Kiri\Websocket\Contract\OnCloseInterface;
use Kiri\Websocket\Contract\OnHandshakeInterface;
use Kiri\Websocket\Contract\OnMessageInterface;
use Kiri\Websocket\Contract\OnOpenInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WsServer;
use Swoole\Coroutine\Http\Server as WhServer;

class Server extends Component
{


	/**
	 * @var EventDispatch
	 */
	#[Inject(EventDispatch::class)]
	public EventDispatch $dispatch;


	public string $host = '0.0.0.0';


	public int $port = 9527;


	public bool $isSsl = false;


	public RouterCollector $router;

	public bool $reuse_port = true;


	private bool $isShutdown = false;

	public Coroutine\Http\Server $server;

	public ExceptionHandlerInterface $exception;


	private int|Handler|null $handler;


	public array|null|Closure $handshake;


	public array|null|Closure $message;


	public array|null|Closure $close;


	public array|null|Closure $disconnect;


	/**
	 * @param ContainerInterface $container
	 * @param DataGrip $collector
	 * @param Dispatcher $dispatcher
	 * @param ResponseEmitter $emitter
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(
		public ContainerInterface $container,
		public DataGrip           $collector,
		public Dispatcher         $dispatcher,
		public ResponseEmitter    $emitter,
		array                     $config = [])
	{
		parent::__construct($config);

		$this->router = $this->collector->get('wss');
	}


	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ConfigException
	 */
	public function init(): void
	{
		$exception = Config::get('exception.http', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exception))) {
			$exception = ExceptionHandlerDispatcher::class;
		}
		$this->exception = $this->container->get($exception);

		$this->handler = $this->router->find('/', 'GET');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception
	 */
	public function onHandshake(Request $request, Response $response): void
	{
		try {
			/** @var \Kiri\Message\Response $psrResponse */
			$psrResponse = Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());

			/** @var ServerRequest $psrRequest */
			$psrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

			$handler = $this->router->find('/', 'GET');
			if (is_integer($handler)) {
				$psrResponse->withContent('Allow Method[' . $request->getMethod() . '].')->withStatus(405);
			} else if (is_null($handler)) {
				$psrResponse->withContent('Page not found.')->withStatus(404);
			} else {
				$psrResponse = $this->dispatcher->with($handler)->handle($psrRequest);

				$executor = $handler->callback;
				$response->upgrade();
				if ($handler instanceof OnHandshakeInterface) {
					$statusCode = $handler->onHandshake($request, $response);
					$response->setStatusCode($statusCode);
					$response->write("connect ok.");
				}
				if ($executor instanceof OnOpenInterface) {
					$executor->onOpen($this->server, $request);
				}
				while (true) {
					$frame = $response->recv();
					if ($frame === false || $frame instanceof CloseFrame || $frame === '') {
						$handler->onClose($this->server, $response->fd);
						break;
					}
					$handler->onMessage($this->server, $frame);
				}
			}
		} catch (\Throwable $throwable) {
			$psrResponse = $this->exception->emit($throwable, di(CResponse::class));
		} finally {
			$this->emitter->sender($response, $psrResponse);
		}
	}


	/**
	 * @param WsServer|Coroutine\Http\Server $server
	 * @param Frame $frame
	 * @return void
	 */
	public function onMessage(WsServer|WhServer $server, Frame $frame): void
	{
		$handler = $this->handler->callback;
		if ($handler instanceof OnMessageInterface) {
			$handler->onMessage($server, $frame);
		}
	}


	/**
	 * @param WsServer|Coroutine\Http\Server $server
	 * @param int $fd
	 * @return void
	 */
	public function onClose(WsServer|WhServer $server, int $fd): void
	{
		$handler = $this->handler->callback;
		if ($handler instanceof OnCloseInterface) {
			$handler->onClose($server, $fd);
		}
	}


	/**
	 * @param WsServer|WhServer $server
	 * @param Request $request
	 * @return void
	 */
	public function onOpen(WsServer|WhServer $server, Request $request): void
	{
		$handler = $this->handler->callback;
		if ($handler instanceof OnOpenInterface) {
			$handler->onOpen($server, $request);
		}
	}


}
