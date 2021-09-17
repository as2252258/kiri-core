<?php

namespace Http\Handler;

use Exception;
use Http\Context\Context;
use Http\Handler\Abstracts\HandlerManager;
use Http\Message\ServerRequest;
use Http\Message\Stream;
use Http\Route\MiddlewareManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Server\Constrict\RequestInterface;
use Server\Constrict\ResponseEmitter;
use Swoole\Http\Request;
use Swoole\Http\Response;

class TestRequest
{


    private ?ResponseEmitter $response = null;


    /**
     * @param Request $request
     * @param Response $response
     * @throws Exception
     */
    public function onRequest(Request $request, Response $response): void
    {
        try {
            [$PsrRequest, $PsrResponse] = $this->initRequestResponse($request);
            /** @var Handler $handler */
            $handler = HandlerManager::get($request->server['request_uri'], $request->getMethod());
            if (is_integer($handler)) {
                $PsrResponse->withStatus($handler)->withBody(null);
            } else if (is_null($handler)) {
                $PsrResponse->withStatus(404)->withBody(null);
            } else {
                $PsrResponse = $this->handler($handler, $PsrRequest);
            }
        } catch (\Throwable $throwable) {
            $PsrResponse = $this->throwable($throwable);
        } finally {
            $this->response->sender($response, $PsrResponse);
        }
    }


    /**
     * @param \Throwable $throwable
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function throwable(\Throwable $throwable): ResponseInterface
    {
        return \response()->withStatus($throwable->getCode())
            ->withContentType(\Http\Message\Response::CONTENT_TYPE_HTML)
            ->withBody(new Stream(jTraceEx($throwable)));
    }


    /**
     * @param \Http\Handler\Handler $handler
     * @param $PsrRequest
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function handler(Handler $handler, $PsrRequest): ResponseInterface
    {
        $middlewares = MiddlewareManager::get($handler->callback);

        $dispatcher = new Dispatcher($handler, $middlewares);

        return $dispatcher->handle($PsrRequest);
    }


    /**
     * @param Request $request
     * @return array<ServerRequestInterface, ResponseInterface>
     * @throws Exception
     */
    private function initRequestResponse(Request $request): array
    {
        $PsrResponse = Context::setContext(ResponseInterface::class, new \Http\Message\Response());

        $PsrRequest = Context::setContext(RequestInterface::class, ServerRequest::createServerRequest($request));

        return [$PsrRequest, $PsrResponse];
    }

}
