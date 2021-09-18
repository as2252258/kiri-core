<?php

namespace Http\Handler;

use Closure;
use Http\Handler\Abstracts\HandlerManager;
use Http\Route\MiddlewareManager;

class Router
{


	private array $groupTack = [];


	/**
	 * @param string $route
	 * @param string|Closure $closure
	 * @param array $options
	 */
	public function get(string $route, string|Closure $closure, array $options = [])
	{
		array_push($this->groupTack, $options);

		$this->addRoute('GET', $route, $closure);

		array_pop($this->groupTack);
	}


	/**
	 * @param string $route
	 * @param string|Closure $closure
	 * @param array $options
	 */
	public function post(string $route, string|Closure $closure, array $options = [])
	{
		array_push($this->groupTack, $options);

		$this->addRoute('POST', $route, $closure);

		array_pop($this->groupTack);
	}


	/**
	 * @param string|array $method
	 * @param string $route
	 * @param string|Closure $closure
	 * @throws \ReflectionException
	 */
	public function addRoute(string|array $method, string $route, string|Closure $closure)
	{
		if (!is_array($method)) $method = [$method];
		$route = $this->getPath($route);
		if (is_string($closure)) {
			$closure = explode('@', $closure);
			$controller = $this->addNamespace($closure[0]);
			if (!class_exists($controller)) {
				return;
			}
			$this->addMiddlewares($controller, $closure[0]);
		}
		foreach ($method as $value) {
			HandlerManager::add($route, $value,
				new Handler($route, $closure));
		}
	}


	/**
	 * @param array $config
	 * @param Closure $closure
	 */
	public function group(array $config, Closure $closure)
	{
		array_push($this->groupTack, $config);

		call_user_func($closure, $this);

		array_pop($this->groupTack);
	}


	/**
	 * @param string $route
	 * @return string
	 */
	protected function getPath(string $route): string
	{
		$route = ltrim($route, '/');
		$prefix = array_column($this->groupTack, 'prefix');
		if (empty($prefix = array_filter($prefix))) {
			return '/' . $route;
		}
		return '/' . implode('/', $prefix) . $route;
	}


	/**
	 * @param $controller
	 * @param $method
	 */
	protected function addMiddlewares($controller, $method)
	{
		$middleware = array_column($this->groupTack, 'middleware');
		if (empty($middleware = array_filter($middleware))) {
			return;
		}
		MiddlewareManager::add($controller, $method, $middleware);
	}


	/**
	 * @param $class
	 * @return string|null
	 */
	protected function addNamespace($class): ?string
	{
		$middleware = array_column($this->groupTack, 'namespace');
		if (empty($middleware = array_filter($middleware))) {
			return $class;
		}
		$middleware[] = $class;
		return implode('\\', array_map(function ($value) {
			return trim($value, '\\');
		}, $middleware));
	}


}
