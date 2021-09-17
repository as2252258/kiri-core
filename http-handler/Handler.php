<?php

namespace Http\Handler;

use Closure;
use Kiri\Kiri;

class Handler
{


	public string $route = '';


	public array|Closure|null $callback;


	public ?array $params = [];


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
