<?php

namespace Http\Handler;

use Closure;
use Exception;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Abstracts\MiddlewareManager;
use Kiri\Abstracts\Logger;
use Kiri\Kiri;
use Throwable;

class Router
{


	private array $groupTack = [];


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function socket($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'SOCKET');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function post($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'POST');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function get($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'GET');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function options($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'OPTIONS');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @throws
	 */
	public static function any($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		foreach ($router->methods as $method) {
			$router->addRoute($route, $handler, $method);
		}
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function delete($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'DELETE');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws Exception
	 */
	public static function head($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'HEAD');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return void
	 * @throws
	 */
	public static function put($route, $handler): void
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->addRoute($route, $handler, 'PUT');
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
			$closure[0] = $this->addNamespace($closure[0]);
			if (!class_exists($closure[0])) {
				return;
			}
			$this->addMiddlewares(...$closure);
		}
		foreach ($method as $value) {
			HandlerManager::add($route, $value, new Handler($route, $closure));
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


	/**
	 * @throws Exception
	 */
	public function _loader()
	{
		$this->loadRouteDir(APP_PATH . 'routes');
	}


	/**
	 * @param $path
	 * @throws Exception
	 * 加载目录下的路由文件
	 */
	private function loadRouteDir($path)
	{
		$files = glob($path . '/*');
		for ($i = 0; $i < count($files); $i++) {
			if (is_dir($files[$i])) {
				$this->loadRouteDir($files[$i]);
			} else {
				$this->loadRouterFile($files[$i]);
			}
		}
	}


	/**
	 * @param $files
	 * @throws Exception
	 */
	private function loadRouterFile($files)
	{
		try {
			include_once "$files";
		} catch (Throwable $exception) {
			di(Logger::class)->error('router', [
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine(),
			]);
		} finally {
			if (isset($exception)) {
				unset($exception);
			}
		}
	}


}
