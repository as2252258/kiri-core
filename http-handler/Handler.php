<?php

namespace Http\Handler;

use Closure;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Events\EventProvider;
use Kiri\Kiri;
use Server\Events\OnAfterWorkerStart;

class Handler
{


	public string $route = '';


	public array|Closure|null $callback;


	public ?array $params = [];


	public array $_middlewares = [];


	/**
	 * @param string $route
	 * @param array|Closure $callback
	 * @throws \ReflectionException
	 */
	public function __construct(string $route, array|Closure $callback)
	{
		$this->route = $route;

		$this->_injectParams($callback);

		$this->callback = $callback;

		$dispatcher = Kiri::getDi()->get(EventProvider::class);
		$dispatcher->on(OnAfterWorkerStart::class, function () {
			if ($this->route instanceof Closure) {
				return;
			}
			$this->_middlewares = MiddlewareManager::get($this->route);
		});
	}


	/**
	 * @param array|Closure $callback
	 * @throws \ReflectionException
	 */
	private function _injectParams(array|Closure $callback)
	{
		$container = Kiri::getDi();
		if (!($callback instanceof Closure)) {
			$this->params = $container->getMethodParameters($callback[0], $callback[1]);
		} else {
			$this->params = $container->getFunctionParameters($callback);
		}
	}
}
