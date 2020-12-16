<?php
declare(strict_types=1);

namespace HttpServer\Route;

use Closure;
use Exception;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\IInterface\RouterInterface;
use HttpServer\Application;
use HttpServer\Route\Annotation\Http;
use Snowflake\Abstracts\Config;
use Snowflake\Core\JSON;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;

defined('ROUTER_TREE') or define('ROUTER_TREE', 1);
defined('ROUTER_HASH') or define('ROUTER_HASH', 2);

/**
 * Class Router
 * @package Snowflake\Snowflake\Route
 */
class Router extends Application implements RouterInterface
{
	/** @var Node[] $nodes */
	public array $nodes = [];
	public array $groupTacks = [];
	public ?string $dir = 'App\\Http\\Controllers';

	const NOT_FOUND = 'Page not found or method not allowed.';

	/** @var string[] */
	public array $methods = ['get', 'post', 'options', 'put', 'delete', 'receive'];


	public ?Closure $middleware = null;

	public bool $useTree = false;

	private bool $reading = false;


	/**
	 * @param Closure $middleware
	 */
	public function setMiddleware(\Closure $middleware): void
	{
		$this->middleware = $middleware;
	}


	/**
	 * @throws ConfigException
	 * 初始化函数路径
	 */
	public function init()
	{
		$this->dir = Config::get('http.namespace', false, $this->dir);
	}

	/**
	 * @param bool $useTree
	 */
	public function setUseTree(bool $useTree): void
	{
		$this->useTree = $useTree;
	}


	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return ?Node
	 * @throws ConfigException
	 */
	public function addRoute($path, $handler, $method = 'any'): ?Node
	{
		$method = strtolower($method);
		if (!isset($this->nodes[$method])) {
			$this->nodes[$method] = [];
		}

		$useTree = Config::get('router', false, ROUTER_HASH);
		if ($useTree == ROUTER_TREE) {
			return $this->tree($path, $handler, $method);
		} else {
			return $this->hash($path, $handler, $method);
		}
	}


	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return ?Node
	 */
	private function hash($path, $handler, $method = 'any'): ?Node
	{
		$path = $this->resolve($path);

		$this->nodes[$method][$path] = $this->NodeInstance($path, 0, $method);

		return $this->nodes[$method][$path]->bindHandler($handler);
	}


	/**
	 * @param $path
	 * @return string
	 */
	private function resolve($path)
	{
		$paths = array_column($this->groupTacks, 'prefix');
		if (empty($paths)) {
			return '/' . ltrim($path, '/');
		}
		$paths = '/' . implode('/', $paths);
		if ($path != '/') {
			return $paths . '/' . ltrim($path, '/');
		}
		return $paths . '/';
	}


	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return Node
	 */
	private function tree($path, $handler, $method = 'any'): Node
	{
		list($first, $explode) = $this->split($path);

		$parent = $this->nodes[$method][$first] ?? null;
		if (empty($parent)) {
			$parent = $this->NodeInstance($first, 0, $method);
			$this->nodes[$method][$first] = $parent;
		}
		if ($first !== '/') {
			$parent = $this->bindNode($parent, $explode, $method);
		}
		return $parent->bindHandler($handler);
	}


	/**
	 * @param Node $parent
	 * @param array $explode
	 * @param $method
	 * @return Node
	 */
	private function bindNode(Node $parent, array $explode, $method): Node
	{
		$a = 0;
		if (empty($explode)) {
			return $parent->addChild($this->NodeInstance('/', $a, $method), '/');
		}
		foreach ($explode as $value) {
			if (empty($value)) {
				continue;
			}
			++$a;

			$search = $parent->findNode($value);
			if ($search === null) {
				$parent = $parent->addChild($this->NodeInstance($value, $a, $method), $value);
			} else {
				$parent = $search;
			}
		}
		return $parent;
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed
	 * @throws ConfigException
	 */
	public function socket($route, $handler): mixed
	{
		return $this->addRoute($route, $handler, 'socket');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return Node|null
	 * @throws ConfigException
	 */
	public function post($route, $handler): ?Node
	{
		return $this->addRoute($route, $handler, 'post');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function get($route, $handler)
	{
		return $this->addRoute($route, $handler, 'get');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function options($route, $handler)
	{
		return $this->addRoute($route, $handler, 'options');
	}


	/**
	 * @param $port
	 * @param Closure $closure
	 * @throws
	 */
	public function listen(int $port, Closure $closure)
	{
		$stdClass = Snowflake::createObject(Handler::class);
		$this->group(['prefix' => $port], $closure, $stdClass);
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return Any
	 */
	public function any($route, $handler)
	{
		$nodes = [];
		foreach (['get', 'post', 'options', 'put', 'delete'] as $method) {
			$nodes[] = $this->addRoute($route, $handler, $method);
		}
		return new Any($nodes);
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function delete($route, $handler)
	{
		return $this->addRoute($route, $handler, 'delete');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function put($route, $handler)
	{
		return $this->addRoute($route, $handler, 'put');
	}

	/**
	 * @param $value
	 * @param $index
	 * @param $method
	 * @return Node
	 * @throws
	 */
	public function NodeInstance($value, $index = 0, $method = 'get'): Node
	{
		$node = new Node();
		$node->childes = [];
		$node->path = $value;
		$node->index = $index;
		$node->method = $method;
		$node->namespace = $this->loadNamespace($method);

		$name = array_column($this->groupTacks, 'middleware');
		if ($this->middleware instanceof \Closure) {
			$node->addMiddleware($this->middleware);
		}
		$node->bindMiddleware($name);

		return $node;
	}


	/**
	 * @param $method
	 * @return array
	 */
	private function loadNamespace($method): array
	{
		$name = array_column($this->groupTacks, 'namespace');
		if ($method == 'package' || $method == 'receive') {
			$dir = 'App\\Listener';
		} else {
			$dir = $this->dir;
		}
		array_unshift($name, $dir);
		return array_filter($name);
	}

	/**
	 * @param array $config
	 * @param callable $callback
	 * 路由分组
	 * @param null $stdClass
	 */
	public function group(array $config, callable $callback, $stdClass = null)
	{
		$this->groupTacks[] = $config;
		if ($stdClass) {
			$callback($stdClass);
		} else {
			$callback($this);
		}
		array_pop($this->groupTacks);
	}

	/**
	 * @return string
	 */
	public function addPrefix(): string
	{
		$prefix = array_column($this->groupTacks, 'prefix');

		$prefix = array_filter($prefix);

		if (empty($prefix)) {
			return '';
		}

		return '/' . implode('/', $prefix);
	}

	/**
	 * @param array|null $explode
	 * @param $method
	 * @return Node|null
	 * 查找指定路由
	 */
	public function tree_search(?array $explode, $method): ?Node
	{
		if (empty($explode)) {
			return $this->nodes[$method]['/'] ?? null;
		}
		$first = array_shift($explode);
		if (!($parent = $this->nodes[$method][$first] ?? null)) {
			return null;
		}
		if (empty($explode)) {
			return $parent->findNode('/');
		}
		while ($value = array_shift($explode)) {
			$node = $parent->findNode($value);
			if (!$node) {
				break;
			}
			$parent = $node;
		}
		return $parent;
	}

	/**
	 * @param $path
	 * @return array
	 * '*'
	 */
	public function split($path)
	{
		$prefix = $this->addPrefix();
		$path = ltrim($path, '/');
		if (!empty($prefix)) {
			$path = $prefix . '/' . $path;
		}

		$explode = array_filter(explode('/', $path));
		if (empty($explode)) {
			return ['/', []];
		}

		$first = array_shift($explode);
		if (empty($explode)) {
			$explode = [];
		}
		return [$first, $explode];
	}

	/**
	 * @return array
	 */
	public function each()
	{
		$paths = [];
		foreach ($this->nodes as $node) {
			/** @var Node[] $node */
			foreach ($node as $_node) {
				if ($_node->path == '/') {
					continue;
				}
				$path = strtoupper($_node->method) . ' : ' . $_node->path;
				if (!empty($_node->childes)) {
					$path = $this->readByChild($_node->childes, $path);
				}
				$paths[] = $path;
			}
		}
		return $this->readByArray($paths);
	}

	/**
	 * @param $array
	 * @param array $returns
	 * @return array
	 */
	private function readByArray($array, $returns = [])
	{
		foreach ($array as $value) {
			if (empty($value)) {
				continue;
			}
			if (is_array($value)) {
				$returns = $this->readByArray($value, $returns);
			} else {
				[$method, $route] = explode(' : ', $value);

				$returns[] = ['method' => $method, 'route' => $route];
			}
		}
		return $returns;
	}


	/**
	 * @param $child
	 * @param string $paths
	 * @return array
	 */
	private function readByChild($child, $paths = '')
	{
		$newPath = [];
		/** @var Node $item */
		foreach ($child as $item) {
			if ($item->path == '/') {
				continue;
			}
			if (!empty($item->childes)) {
				$newPath[] = $this->readByChild($item->childes, $paths . '/' . $item->path);
			} else {
				[$first, $route] = explode(' : ', $paths);

				$newPath[] = strtoupper($item->method) . ' : ' . $route . '/' . $item->path;
			}
		}
		return $newPath;
	}


	/**
	 * @return mixed
	 * @throws
	 */
	public function dispatch(): mixed
	{
		if (!($node = $this->find_path(\request()))) {
			return send(self::NOT_FOUND, 404);
		} else {
			send($response = $node->dispatch(), 200);
			if (!$node->hasAfter()) {
				return null;
			}
			return $node->afterDispatch($response);
		}
	}


	/**
	 * @param $exception
	 * @return false|int|mixed|string
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function exception($exception)
	{
		return Snowflake::app()->getLogger()->exception($exception);
	}


	/**
	 * @param Request $request
	 * @return Node|false|int|mixed|string|null
	 * 树干搜索
	 */
	private function find_path(Request $request)
	{
		$method = $request->getMethod();
		if (!isset($this->nodes[$method])) {
			return null;
		}
		$methods = $this->nodes[$method];
		$uri = $request->headers->get('request_uri', '/');
		if (isset($methods[$uri])) {
			return $methods[$uri];
		}
		if (!$request->isOption || !isset($methods['/'])) {
			return null;
		}
		return $methods['/'];
	}


	/**
	 * @param $uri
	 * @param $method
	 * @return Node|null
	 */
	public function search($uri, $method): Node|null
	{
		if (!isset($this->nodes[$method])) {
			return null;
		}
		$methods = $this->nodes[$method];
		if (isset($methods[$uri])) {
			return $methods[$uri];
		}
		return $methods['/'] ?? null;
	}


	/**
	 * @param $request
	 * @return Node|null
	 */
	private function search_options($request)
	{
		$method = $request->getMethod();
		if (!isset($this->nodes[$method])) {
			return null;
		}
		if (!isset($this->nodes[$method]['*'])) {
			return null;
		}
		return $this->nodes[$method]['*'];
	}


	/**
	 * @param $request
	 * @return Node|null
	 * 树杈搜索
	 */
	private function Branch_search($request)
	{
		$node = $this->tree_search($request->getExplode(), $request->getMethod());
		if ($node instanceof Node) {
			return $node;
		}
		if (!$request->isOption) {
			return null;
		}
		$node = $this->tree_search(['*'], $request->getMethod());
		if (!($node instanceof Node)) {
			return null;
		}
		return $node;
	}


	/**
	 * @throws
	 */
	public function loadRouterSetting()
	{
		$this->loadRouteDir(APP_PATH . '/routes');
	}

	/**
	 * @param $path
	 * @throws Exception
	 * 加载目录下的路由文件
	 */
	private function loadRouteDir($path)
	{
		$files = glob($path . '/*');
		$this->reading = true;
		for ($i = 0; $i < count($files); $i++) {
			if (is_dir($files[$i])) {
				$this->loadRouteDir($files[$i]);
			} else {
				$this->loadRouterFile($files[$i]);
			}
		}
		$this->reading = false;
	}


	/**
	 * @param $files
	 * @throws Exception
	 */
	private function loadRouterFile($files)
	{
		try {
			$router = $this;
			include_once "{$files}";
		} catch (\Throwable $exception) {
			$this->error($exception->getMessage());
		} finally {
			if (isset($exception)) {
				unset($exception);
			}
		}
	}

}
