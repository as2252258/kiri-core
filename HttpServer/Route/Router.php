<?php
declare(strict_types=1);

namespace HttpServer\Route;

use Closure;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Request;
use HttpServer\IInterface\RouterInterface;

use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

defined('ROUTER_TREE') or define('ROUTER_TREE', 1);
defined('ROUTER_HASH') or define('ROUTER_HASH', 2);

/**
 * Class Router
 * @package Snowflake\Snowflake\Route
 */
class Router extends HttpService implements RouterInterface
{
	/** @var Node[] $nodes */
	public array $nodes = [];
	public array $groupTacks = [];
	public ?string $dir = 'App\\Http\\Controllers';

	public array $hashMap = [];

	const NOT_FOUND = 'Page not found or method not allowed.';

	/** @var string[] */
	public array $methods = ['get', 'post', 'options', 'put', 'delete', 'receive'];


	public ?Closure $middleware = null;

	public bool $useTree = false;


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
	 * @param $port
	 * @param Closure|array|string $closure
	 * @param null $method
	 * @throws
	 */
	public function addPortListen($port, Closure|array|string $closure, $method = null)
	{
		if (is_string($closure)) {
			if (empty($method)) {
				throw new NotFindClassException($closure . '::' . $method);
			}
			$_closure = Snowflake::createObject($closure);
			if (!method_exists($_closure, $method)) {
				throw new NotFindClassException($closure . '::' . $method);
			}
		}
		$this->addRoute('add-port-listen/port_' . $port, $closure, 'listen');
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

		$useTree = Config::get('router', false, ROUTER_TREE);
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
	#[Pure] private function resolve($path): string
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
			$this->nodes[$method][$first] = $parent = $this->NodeInstance('/', 0, $method);
		}

		if ($first !== '/') {
			$parent = $this->bindNode($parent, $explode, $method);
		}
		$parent->path = $path;
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
	 * @return Node|null
	 * @throws
	 */
	public function socket($route, $handler): ?Node
	{
		return $this->addRoute($route, $handler, 'socket');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return Node|null
	 * @throws
	 */
	public function post($route, $handler): ?Node
	{
		return $this->addRoute($route, $handler, 'post');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return Node|null
	 * @throws
	 */
	public function get($route, $handler): ?Node
	{
		return $this->addRoute($route, $handler, 'get');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return Node|null
	 * @throws
	 */
	public function options($route, $handler): ?Node
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
	 * @throws
	 */
	public function any($route, $handler): Any
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
	 * @return Node|null
	 * @throws
	 */
	public function delete($route, $handler): ?Node
	{
		return $this->addRoute($route, $handler, 'delete');
	}

	/**
	 * @param $route
	 * @param $handler
	 * @return Node|null
	 * @throws
	 */
	public function put($route, $handler): ?Node
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

		if (is_array($name)) foreach ($name as $item) {
			$node->addMiddleware($item);
		}

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
	public function split($path): array
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
	public function each(): array
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
	private function readByArray($array, $returns = []): array
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
	private function readByChild($child, $paths = ''): array
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
		try {
			if (!($node = $this->find_path(\request()))) {
				return send(self::NOT_FOUND);
			}
			send($response = $node->dispatch(), 200);
			if (!$node->hasAfter()) {
				return null;
			}
			return $node->afterDispatch($response);
		} catch (\Throwable $exception) {
			$this->addError($exception);

			return send($exception->getMessage(), 200);
		}
	}


	/**
	 * @param $exception
	 * @return mixed
	 * @throws ComponentException
	 * @throws Exception
	 */
	private function exception($exception): mixed
	{
		return Snowflake::app()->getLogger()->exception($exception);
	}


	/**
	 * @param Request $request
	 * @return Node|null 树干搜索
	 * 树干搜索
	 * @throws ConfigException
	 */
	public function find_path(Request $request): ?Node
	{
		$useTree = Config::get('router', false, ROUTER_TREE);
		if ($useTree === ROUTER_TREE) {
			return $this->Branch_search($request);
		}

		$method = $request->getMethod();
		$uri = $request->headers->get('request_uri', '/');

		if (!isset($this->nodes[$method])) {
			return null;
		}
		$methods = $this->nodes[$method];
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
	private function search_options($request): ?Node
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
	 * @param Request $request
	 * @return Node|null
	 * 树杈搜索
	 */
	private function Branch_search(Request $request): ?Node
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
			$router = $this;
			include_once "{$files}";
		} catch (\Throwable $exception) {
			$this->error($exception);
		} finally {
			if (isset($exception)) {
				unset($exception);
			}
		}
	}

}
