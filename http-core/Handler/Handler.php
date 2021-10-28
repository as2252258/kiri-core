<?php

namespace Http\Handler;

use Annotation\Aspect;
use Closure;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Di\NoteManager;
use Kiri\Events\EventProvider;
use Kiri\IAspect;
use Kiri\Kiri;
use Server\Events\OnAfterWorkerStart;

class Handler
{


	public string $route = '';


	public array|Closure|null $callback;


	public ?array $params = [];


	public IAspect $_aspect;


	public ?array $_middlewares = [];


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
			if ($this->callback instanceof Closure) {
				return;
			}
			$this->_middlewares = MiddlewareManager::get($this->callback);

			$aspect = NoteManager::getSpecify_annotation(Aspect::class, $this->callback[0], $this->callback[1]);

			$this->callback[0] = Kiri::getDi()->get($this->callback[0]);
			if (!is_null($aspect)) {
				$this->recover($aspect);
			}
		});
	}


	/**
	 * @param Aspect $aspect
	 */
	public function recover(Aspect $aspect)
	{
		$aspect = Kiri::getDi()->get($aspect->aspect);
		if (empty($aspect)) {
			return;
		}
		$this->_aspect = $aspect;
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
