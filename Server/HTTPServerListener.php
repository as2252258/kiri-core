<?php

namespace Server;

use Annotation\Inject;
use Exception;
use HttpServer\Exception\RequestException;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HSRequest;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
use Server\Events\OnAfterRequest;
use Snowflake\Event;
use Snowflake\Events\EventDispatch;
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

    /** @var \HttpServer\Http\Response|mixed */
    #[Inject(\HttpServer\Http\Response::class)]
    public \HttpServer\Http\Response $response;


    /** @var EventDispatch */
    #[Inject(EventDispatch::class)]
    public EventDispatch $eventDispatch;


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
                throw new RequestException('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', 404);
            }
            $data = $node->dispatch();
        } catch (Error | Throwable $exception) {
            $response->header('Content-Type','text/html;charset=utf-8');
            $code = $exception->getCode() == 0 ? 500 : $exception->getCode();
            $data = $code ? $exception->getMessage() : jTraceEx($exception);
        } finally {
            $this->requestEnd($data, $response, $code ?? 200);
        }
    }


    /**
     * @param mixed $data
     * @param \Swoole\Http\Response $response
     */
    protected function requestEnd(mixed $data, Response $response, $code)
    {
        $sResponse = $this->response->getBuilder($data, $response);

        $response->setStatusCode($code);
        $response->end($sResponse->getContent());

        $this->eventDispatch->dispatch(new OnAfterRequest());
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
