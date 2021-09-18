<?php

namespace Http\Handler\Abstracts;

use Http\Handler\Handler as CHl;
use Kiri\Kiri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


abstract class Handler implements RequestHandlerInterface
{


	private int $offset = 0;


	/**
	 * @param CHl $handler
	 * @param array|null $middlewares
	 */
	public function __construct(protected CHl $handler, protected ?array $middlewares)
	{
	}


	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	protected function execute(ServerRequestInterface $request): ResponseInterface
	{
		if (empty($this->middlewares) || !isset($this->middlewares[$this->offset])) {
			return $this->dispatcher();
		}

		$middleware = $this->middlewares[$this->offset];
		if (!($middleware instanceof MiddlewareInterface)) {
			throw new \Exception('get_implements_class($middleware) not found method process.');
		}

		++$this->offset;

		return $middleware->process($request, $this);
	}


	/**
	 * @return mixed
	 */
	protected function dispatcher(): mixed
	{
		if ($this->handler->callback instanceof \Closure) {
			return call_user_func($this->handler->callback, ...$this->handler->params);

		}
		[$controller, $action] = $this->handler->callback;

		$controller = Kiri::getDi()->get($controller);

		return call_user_func([$controller, $action], ...$this->handler->params);
	}


}
