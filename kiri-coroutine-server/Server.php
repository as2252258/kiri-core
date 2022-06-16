<?php

namespace Kiri\CoroutineServer;

use Closure;
use Co\WaitGroup;
use Exception;
use Kiri\Abstracts\Component;
use Kiri\Annotation\Inject;
use Kiri\Context;
use Kiri\Di\ContainerInterface;
use Kiri\Events\EventDispatch;
use Kiri\Message\Constrict\RequestInterface;
use Kiri\Message\Constrict\ResponseInterface;
use Kiri\Message\ServerRequest;
use Kiri\Server\Contract\OnCloseInterface;
use Kiri\Server\Contract\OnHandshakeInterface;
use Kiri\Server\Contract\OnMessageInterface;
use Kiri\Server\Contract\OnOpenInterface;
use Kiri\Server\Events\OnBeforeShutdown;
use Kiri\Server\Events\OnWorkerStart;
use Kiri\ToArray;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;

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


	public array $router = [];


	public bool $reuse_port = true;


	private bool $isShutdown = false;

	public Coroutine\Http\Server $server;


	/**
	 * @param ContainerInterface $container
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(public ContainerInterface $container, array $config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @return void
	 */
	public function init(): void
	{
		$this->server = new Coroutine\Http\Server($this->host, $this->port, $this->isSsl, $this->reuse_port);
		$this->server->set(['max_coroutine' => 500000]);
		$this->server->handle('/', [$this, 'actor']);
	}


	/**
	 * @param Coroutine\WaitGroup $group
	 * @return void
	 */
	public function run(Coroutine\WaitGroup $group): void
	{
		Coroutine::create(function () use ($group) {
			$this->dispatch->dispatch(new OnWorkerStart(null, 0));

			$this->start($group);
		});
	}


	/**
	 * @param WaitGroup $group
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function start(WaitGroup $group): void
	{
		$this->server->start();
		$this->dispatch->dispatch(new OnBeforeShutdown());
		if ($this->isShutdown === false) {
			$this->start($group);
		} else {
			$group->done();
		}
	}


	public function stop()
	{
		$this->isShutdown = true;
		$this->server->shutdown();
	}

	/**
	 * @param $path
	 * @param $method
	 * @param array|Closure $closure
	 * @return void
	 */
	public function handler($path, $method, array|Closure $closure): void
	{
		$this->router[$path] = [strtolower($method), $closure];
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return mixed
	 * @throws Exception
	 */
	public function actor(Request $request, Response $response): mixed
	{
		Context::setContext(ResponseInterface::class, new \Kiri\Message\Response());
		Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

		if ($request['request_method'] === 'HEAD') {
			return $this->write('', $response, 200);
		}

		[$method, $handler] = $this->router[$request['request_uri']] ?? [null, null];
		if (is_null($handler)) {
			return $this->write('Page not found.', $response, 404);
		}
		if (!is_callable($handler, true)) {
			return $this->write('Page not found.', $response, 404);
		}
		if ($method !== $request['request_method']) {
			return $this->write('Page allow.', $response, 405);
		}
		if (isset($request->header['upgrade']) && $request->header['upgrade'] == 'websocket') {
			defer(function () use ($handler, $request) {
				if (!$handler instanceof OnOpenInterface) {
					return;
				}
				$handler->onOpen($this->server, $request);
			});
			if ($handler instanceof OnHandshakeInterface) {
				$handler->onHandshake($request, $response);
			} else {
				$response->upgrade();
			}
			while (true) {
				$read = $response->recv();
				if ($read === '' || $read === null || $read instanceof CloseFrame) {
					break;
				}
				if ($handler instanceof OnMessageInterface) {
					$handler->onMessage($this->server, $read);
				}
			}
			if ($handler instanceof OnCloseInterface) {
				$handler->onClose($response->fd);
			}
			return null;
		}

		$params = $this->container->getArgs($handler[1], $handler[0] ?? null);
		$result = call_user_func($handler, ...$params);
		if (is_null($result)) {
			return $this->write("", $response);
		} else {
			return $this->write($result, $response);
		}
	}


	/**
	 * @param mixed $message
	 * @param Response $response
	 * @param int $statusCode
	 * @return mixed
	 */
	private function write(mixed $message, Response $response, int $statusCode = 200): mixed
	{
		$result = $message;
		if ($message instanceof ResponseInterface) {
			$result = $result->getBody()->getContents();
			$response->setStatusCode($message->getStatusCode());
		} else {
			$message = Context::getContext(ResponseInterface::class);
			$response->setStatusCode($statusCode);
		}

		$headers = $message->getHeaders();
		if (is_array($headers)) foreach ($headers as $key => $header) {
			$response->setHeader($key, $header);
		}

		if (!isset($response->header['content-type'])) {
			$response->header('content-type', 'application/json');
		} else if (!isset($response->header['Content-Type'])) {
			$response->header('content-type', 'application/json');
		}

		$headers = $message->getCookieParams();
		if (is_array($headers)) foreach ($headers as $key => $header) {
			$response->cookie($key, ...$header);
		}

		if (is_object($result)) {
			$result = $result instanceof ToArray ? $result->toArray() : get_object_vars($result);
		}
		if (is_array($result)) {
			$result = json_encode($result, JSON_UNESCAPED_UNICODE);
		}
		return $response->end($result);
	}

	/**
	 * @param string $host
	 */
	public function setHost(string $host): void
	{
		$this->host = $host;
	}

	/**
	 * @param int $port
	 */
	public function setPort(int $port): void
	{
		$this->port = $port;
	}

	/**
	 * @param bool $isSsl
	 */
	public function setIsSsl(bool $isSsl): void
	{
		$this->isSsl = $isSsl;
	}

	/**
	 * @param bool $reuse_port
	 */
	public function setReusePort(bool $reuse_port): void
	{
		$this->reuse_port = $reuse_port;
	}

}
