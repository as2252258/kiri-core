<?php

namespace Kiri\Websocket;

use Closure;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Abstracts\Config;
use Kiri\Annotation\Inject;
use Kiri\Context;
use Kiri\Di\ContainerInterface;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;
use Kiri\Message\Abstracts\ExceptionHandlerInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\ExceptionHandlerDispatcher;
use Kiri\Message\Handler\DataGrip;
use Kiri\Message\Handler\Dispatcher;
use Kiri\Message\Handler\RouterCollector;
use Kiri\Message\ResponseEmitter;
use Kiri\Message\Constrict\Response as CResponse;
use Kiri\Websocket\Contract\OnCloseInterface;
use Kiri\Websocket\Contract\OnDisconnectInterface;
use Kiri\Websocket\Contract\OnHandshakeInterface;
use Kiri\Websocket\Contract\OnMessageInterface;
use Kiri\Websocket\Contract\OnOpenInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame;


/**
 *
 */
class Server extends Component implements OnHandshakeInterface, OnMessageInterface, OnCloseInterface, OnDisconnectInterface, OnOpenInterface
{


	/**
	 * @var EventDispatch
	 */
	#[Inject(EventDispatch::class)]
	public EventDispatch $dispatch;


	/**
	 * @var string
	 */
	public string $host = '0.0.0.0';


	/**
	 * @var int
	 */
	public int $port = 9527;


	/**
	 * @var RouterCollector
	 */
	public RouterCollector $router;


	/**
	 * @var Coroutine\Http\Server
	 */
	public Coroutine\Http\Server $server;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exception;


	/**
	 * @var array|Closure|null
	 */
	public array|null|Closure $message;


	/**
	 * @var array|Closure|null
	 */
	public array|null|Closure $close;


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
		$exception = Config::get('exception.websocket', ExceptionHandlerDispatcher::class);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exception))) {
			$exception = ExceptionHandlerDispatcher::class;
		}
		$this->exception = $this->container->get($exception);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception
	 */
	public function OnHandshake(Request $request, Response $response): void
	{
		try {
			/** @var \Kiri\Message\Response $psrResponse */
			$psrResponse = Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());

			$handler = $this->router->find('/', 'GET');
			if (is_integer($handler)) {
				$psrResponse->withContent('Allow Method[' . $request->getMethod() . '].')->withStatus(405);
			} else if (is_null($handler)) {
				$psrResponse->withContent('Page not found.')->withStatus(404);
			} else {
				$executor = $handler->dispatch->handler;

				if (Context::inCoroutine()) {
					$response->upgrade();
					if ($handler instanceof OnHandshakeInterface) {
						$handler->OnHandshake($request, $response);
					}
					if ($executor instanceof OnOpenInterface) {
						$executor->OnOpen($request);
					}
					while (true) {
						$frame = $response->recv();
						if ($frame === false || $frame instanceof CloseFrame || $frame === '') {
							$handler->OnClose($response->fd);
							break;
						}
						$handler->OnMessage($frame);
					}
				} else {
					$this->OnOpen($request);
				}
			}
		} catch (\Throwable $throwable) {
			$psrResponse = $this->exception->emit($throwable, di(CResponse::class));
		} finally {
			$this->emitter->sender($response, $psrResponse);
		}
	}


	/**
	 * @param Frame $frame
	 * @return void
	 */
	public function OnMessage(Frame $frame): void
	{
	}


	/**
	 * @param int $fd
	 * @return void
	 */
	public function OnClose(int $fd): void
	{
	}


	/**
	 * @param int $fd
	 * @return void
	 */
	public function OnDisconnect(int $fd): void
	{
		// TODO: Implement OnDisconnect() method.
	}


	/**
	 * @param Request $request
	 * @return void
	 */
	public function OnOpen(Request $request): void
	{
	}


}
