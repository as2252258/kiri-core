<?php
declare(strict_types=1);

namespace Http\Route;

use Annotation\Inject;
use Closure;
use Exception;
use Http\Abstracts\HttpService;
use Http\Controller;
use Http\Handler\Abstracts\HandlerManager;
use Http\Handler\Handler;
use Http\IInterface\MiddlewareInterface;
use Http\IInterface\RouterInterface;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Config;
use Kiri\Exception\ConfigException;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constrict\RequestInterface;
use Throwable;

defined('ROUTER_TREE') or define('ROUTER_TREE', 1);
defined('ROUTER_HASH') or define('ROUTER_HASH', 2);

/**
 * Class Router
 * @package Kiri\Kiri\Route
 */
class Router extends HttpService implements RouterInterface
{
	/** @var Node[] $nodes */
	public array $nodes = [];
	public array $groupTacks = [];
	public ?string $namespace = 'App\\Http\\Controllers';

	/** @var string[] */
	public array $methods = ['GET', 'POST', 'OPTIONS', 'PUT', 'DELETE', 'RECEIVE', 'HEAD'];


	public ?Closure $middleware = null;

	public int $useTree = ROUTER_TREE;


	/**
	 * @var RequestInterface
	 */
	#[Inject(RequestInterface::class)]
	public RequestInterface $request;


	/**
	 * @param Closure $middleware
	 */
	public function setMiddleware(Closure $middleware): void
	{
		$this->middleware = $middleware;
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 * 初始化函数路径
	 */
	public function init()
	{
		$this->namespace = Config::get('http.namespace', $this->namespace);
	}


	/**
	 * @return mixed
	 * @throws ConfigException
	 * @throws Exception
	 */
	public static function getNamespace(): string
	{
		$router = Kiri::getDi()->get(Router::class);

		return Config::get('http.namespace', $router->namespace);
	}


	/**
	 * @param bool $useTree
	 */
	public function setUseTree(bool $useTree): void
	{
		$this->useTree = $useTree ? ROUTER_TREE : ROUTER_HASH;
	}


	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return ?Node
	 * @throws
	 */
	public function addRoute($path, $handler, string $method = 'any'): ?Node
	{
		if ($handler instanceof Closure) {
			$handler = Closure::bind($handler, di(Controller::class));
		}

		$path = $this->addPrefix() . '/' . ltrim($path, '/');

		if (is_string($handler)) {
			$handler = explode('@', $handler);
		}

		HandlerManager::add($path, $method, new Handler($path, $handler));

		return null;
		return $this->tree($path, $handler, $method);
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
	 * @throws Exception
	 */
	private function tree($path, $handler, string $method = 'any'): Node
	{
		[$parent, $explode] = $this->getRootNode($path, $method);

		if (!empty($explode)) {
			$parent = $this->bindNode($parent, $explode, $method);
		}

		if (!in_array($method, $parent->method)) {
			$parent->method[] = $method;
		}
		return $parent->setHandler($handler, $method, $path);
	}


	/**
	 * @param $root
	 * @param $method
	 * @param bool $create
	 * @return array<Node, array>
	 */
	private function getRootNode($root, $method, bool $create = true): array
	{
		[$start, $explode] = $this->path_recombination($root);
		$parent = $this->nodes[$start] ?? null;
		if ($parent instanceof Node) {
			return [$parent, $explode];
		}
		if ($create) {
			$parent = $this->NodeInstance($root, 0, $method);
			$this->nodes[$start] = $parent;

			return [$parent, $explode];
		}
		return [null, null];
	}


	/**
	 * @param Node $parent
	 * @param array $explode
	 * @param $method
	 * @return Node
	 * @throws Exception
	 */
	private function bindNode(Node $parent, array $explode, $method): Node
	{
		$a = 0;
		foreach ($explode as $value) {
			++$a;
			$search = $parent->findNode($value);
			if ($search === null) {
				$parent = $parent->addChild($this->NodeInstance($value, $a, $method));
			} else {
				$parent = $search;
			}
		}
		return $parent;
	}

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
	 * @param $value
	 * @param int $index
	 * @param string $method
	 * @return Node
	 * @throws
	 */
	public function NodeInstance($value, int $index = 0, string $method = 'GET'): Node
	{
		$node = new Node($this);
		$node->childes = [];
		$node->path = $value;
		$node->index = $index;
		$node->method[] = $method;
		$node->namespace = $this->loadNamespace();

		$name = array_column($this->groupTacks, 'middleware');
		if (is_array($name)) {
			$node->addMiddleware($method, $this->resolve_middleware($name));
		}
		return $node;
	}


	/**
	 * @return Closure|null
	 */
	public function getMiddleware(): ?Closure
	{
		return $this->middleware;
	}


	/**
	 * @param string|array $middleware
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolve_middleware(string|array $middleware): array
	{
		if (is_string($middleware)) {
			$middleware = [$middleware];
		}

		$array = [];
		foreach ($middleware as $value) {
			if (is_array($value)) {
				foreach ($value as $item) {
					$array[] = $this->getMiddlewareInstance($item);
				}
			} else {
				$array[] = $this->getMiddlewareInstance($value);
			}
		}
		return array_filter($array);
	}


	/**
	 * @param $value
	 * @return Closure|array|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function getMiddlewareInstance($value): null|Closure|array
	{
		if (!is_string($value)) {
			return $value;
		}
		$value = Kiri::createObject($value);
		if (!($value instanceof MiddlewareInterface)) {
			return null;
		}
		return [$value, 'onHandler'];
	}


	/**
	 * @return array
	 */
	private function loadNamespace(): array
	{
		$name = array_column($this->groupTacks, 'namespace');
		array_unshift($name, $this->namespace);
		return array_filter($name);
	}

	/**
	 * @param array $config
	 * @param callable $callback
	 * 路由分组
	 */
	public static function group(array $config, callable $callback)
	{
		$router = Kiri::getDi()->get(Router::class);
		$router->groupTacks[] = $config;

		call_user_func($callback);

		array_pop($router->groupTacks);
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
	 * @return Node|null
	 * 查找指定路由
	 * @throws Exception
	 */
	public function tree_search(?array $explode): ?Node
	{
		$start = array_shift($explode);
		foreach ($this->nodes as $node) {
			if ($node->path == $start) {
				$parent = $node;
			}
		}
		if (!isset($parent)) {
			return null;
		}
		while ($value = array_shift($explode)) {
			$node = $parent->findNode($value);
			if (!$node) {
				return null;
			}
			$parent = $node;
		}
		return $parent;
	}

	/**
	 * @param $path
	 * @return array|null
	 */
	public function path_recombination($path): ?array
	{
		$path = $this->addPrefix() . '/' . ltrim($path, '/');
		if ($path === '/') {
			return ['/', null];
		}
		$filter = array_filter(explode('/', $path));
		if (!empty($filter)) {
			return [array_shift($filter), $filter];
		}
		return ['/', null];
	}

	/**
	 * @return array
	 */
	public function each(): array
	{
		$paths = [];
		foreach ($this->nodes as $_node) {
			/** @var Node[] $node */
			$paths[] = ['method' => $_node->method, 'path' => $_node->sourcePath, 'alias' => $_node->getAlias()];
			$paths = $this->getChildes($_node, $paths);
		}
		return $paths;
	}


	/**
	 * @param Node $node
	 * @param array $path
	 * @return array
	 */
	private function getChildes(Node $node, array $path): array
	{
		foreach ($node->childes as $item) {
			$path[] = ['method' => $item->method, 'path' => $item->sourcePath, 'alias' => $item->getAlias()];
			if (!empty($item->childes)) {
				$path = $this->getChildes($item, $path);
			}
		}
		return $path;
	}


	/**
	 * @param $exception
	 * @return mixed
	 * @throws Exception
	 */
	public function exception($exception): mixed
	{
		return Kiri::app()->getLogger()->exception($exception);
	}


	/**
	 * @param RequestInterface $request
	 * @return Node|null
	 * 树杈搜索
	 * @throws Exception
	 */
	public function radix_tree(RequestInterface $request): ?Node
	{
		$path = $request->getUri()->getPath();

		[$parent, $explode] = $this->getRootNode($path, $request->getMethod(), false);
		if ($parent && !empty($explode)) {
			$parent = $this->searchNode($explode, $parent);
		}
		return $parent;
	}


	/**
	 * @param $explode
	 * @param $parent
	 * @return null|Node
	 * @throws Exception
	 */
	private function searchNode($explode, $parent): ?Node
	{
		/** @var Node $parent */
		foreach ($explode as $value) {
			$node = $parent->findNode($value);
			if (!$node) {
				return null;
			}
			$parent = $node;
		}
		return $parent;
	}


	/**
	 * @throws
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
			$this->addError($exception, 'throwable');
		} finally {
			if (isset($exception)) {
				unset($exception);
			}
		}
	}

}
