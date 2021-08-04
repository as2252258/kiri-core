<?php

namespace Server;

use Annotation\Inject;
use Exception;
use HttpServer\Exception\RequestException;
use HttpServer\Http\Request as HSRequest;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
use ReflectionException;
use Server\Abstracts\PageNotFoundException;
use Server\Constrict\Response as CResponse;
use Server\Events\OnAfterRequest;
use Snowflake\Abstracts\Config;
use Snowflake\Events\EventDispatch;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Server\Port;
use Throwable;


/**
 * Class HTTPServerListener
 * @package Server
 */
class HTTPServerListener extends Abstracts\Server
{

	protected static bool|Port $_http;

	use ListenerHelper;

	/** @var Router|mixed */
	#[Inject('router')]
	public Router $router;


	/** @var CResponse|mixed */
	#[Inject(CResponse::class)]
	public CResponse $response;


	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	/**
	 * @var ExceptionHandlerInterface
	 */
	public ExceptionHandlerInterface $exceptionHandler;


	/**
	 * @throws ReflectionException
	 * @throws ConfigException
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$exceptionHandler = Config::get('exception.http', null);
		if (!in_array(ExceptionHandlerInterface::class, class_implements($exceptionHandler))) {
			$exceptionHandler = ExceptionHandlerDispatcher::class;
		}
		$this->exceptionHandler = Snowflake::getDi()->get($exceptionHandler);
	}


	/**
	 * UDPServerListener constructor.
	 * @param Server|Port $server
	 * @param array|null $settings
	 * @return Server|Port
	 * @throws Exception
	 */
	public function bindCallback(Server|Port $server, ?array $settings = []): Server|Port
	{
		$this->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);

		$server->set(array_merge($settings['settings'] ?? [], ['enable_unsafe_event' => false]));
		if (isset($settings['events'][Constant::REQUEST])) {
			$event = $settings['events'][Constant::REQUEST];
			if (is_array($event) && is_string($event[0])) {
				$event[0] = di($event[0]);
			}
			$server->on('request', $event);
		} else {
			$server->on('request', [$this, 'onRequest']);
		}
		$server->on('connect', [$this, 'onConnect']);
		$server->on('close', [$this, 'onClose']);
		return $server;
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response)
	{
		try {
			$node = $this->router->find_path(HSRequest::create($request));
			if (!($node instanceof Node)) {
				throw new PageNotFoundException(404);
			}
			$responseData = $this->response->setContent($node->dispatch())->setStatusCode(200);
		} catch (Error | Throwable $exception) {
			$responseData = $this->exceptionHandler->emit($exception, $this->response);
		} finally {
			$response->end($responseData->configure($response)->getContent());

			$this->eventDispatch->dispatch(new OnAfterRequest());
		}
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
	}

}
