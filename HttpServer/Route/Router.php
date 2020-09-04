<?php


namespace HttpServer\Route;

use Closure;
use Exception;
use HttpServer\Http\Context;
use HttpServer\IInterface\RouterInterface;
use HttpServer\Application;
use HttpServer\Route\Annotation\Annotation;
use Snowflake\Abstracts\Config;
use Snowflake\Core\JSON;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class Router
 * @package Snowflake\Snowflake\Route
 */
class Router extends Application implements RouterInterface
{
	/** @var Node[] $nodes */
	public $nodes = [];
	public $groupTacks = [];
	public $dir = 'App\\Http\\Controllers';

	/** @var string[] */
	public $methods = ['get', 'post', 'options', 'put', 'delete', 'receive'];

	/**
	 * @throws ConfigException
	 * 初始化函数路径
	 */
	public function init()
	{
		$this->dir = Config::get('http.namespace', false, $this->dir);
	}

	/**
	 * @param $path
	 * @param $handler
	 * @param string $method
	 * @return mixed|Node|null
	 * @throws
	 */
	public function addRoute($path, $handler, $method = 'any')
	{
		if (!isset($this->nodes[$method])) {
			$this->nodes[$method] = [];
		}
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
	private function bindNode($parent, $explode, $method)
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
	 * @return Node|mixed|null
	 */
	public function socket($route, $handler)
	{
		return $this->addRoute($route, $handler, 'socket');
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return mixed|Node|null
	 * @throws
	 */
	public function post($route, $handler)
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
	public function NodeInstance($value, $index = 0, $method = 'get')
	{
		$node = new Node();
		$node->childes = [];
		$node->path = $value;
		$node->index = $index;
		$node->method = $method;

		$name = array_column($this->groupTacks, 'namespace');

		$dir = array_column($this->groupTacks, 'dir');
		if (!empty($dir)) {
			array_unshift($name, implode('\\', $dir));
		} else {
			if ($method == 'package' || $method == 'receive') {
				$dir = 'App\\Listener';
			} else {
				$dir = $this->dir;
			}
			array_unshift($name, $dir);
		}

		if (!empty($name) && $name = array_filter($name)) {
			$node->namespace = $name;
		}

		$name = array_column($this->groupTacks, 'middleware');
		if (!empty($name) && $name = array_filter($name)) {
			$node->bindMiddleware($name);
		}

		$options = array_column($this->groupTacks, 'options');
		if (!empty($options) && is_array($options)) {
			$node->bindOptions($options);
		}

		$rules = array_column($this->groupTacks, 'filter');
		$rules = array_shift($rules);
		if (!empty($rules) && is_array($rules)) {
			$node->filter($rules);
		}

		return $node;
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
	public function addPrefix()
	{
		$prefix = array_column($this->groupTacks, 'prefix');

		$prefix = array_filter($prefix);

		if (empty($prefix)) {
			return '';
		}

		return '/' . implode('/', $prefix);
	}

	/**
	 * @param array $explode
	 * @param $method
	 * @return Node|null
	 * 查找指定路由
	 */
	public function tree_search($explode, $method)
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
	public function dispatch()
	{
		$request = Context::getContext('request');
		if (!($node = $this->find_path($request))) {
			return JSON::to(404, 'Page not found or method not allowed.');
		}
		if (empty($node->callback)) {
			return JSON::to(404, 'Page not found.');
		}
		return call_user_func($node->callback, $request);
	}

	/**
	 * @param $request
	 * @return Node|false|int|mixed|string|null
	 */
	private function find_path($request)
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
		$prefix = APP_PATH . 'app/Http/';

		/** @var Annotation $annotation */
		$annotation = Snowflake::app()->annotation;
		$annotation->register('http', Annotation::class);

		$annotation = $annotation->get('http');
		$annotation->registration_notes($prefix . 'Interceptor', 'App\Http\Interceptor');
		$annotation->registration_notes($prefix . 'Limits', 'App\Http\Limits');
		$annotation->registration_notes($prefix . 'Middleware', 'App\Http\Middleware');

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
		} catch (Exception $exception) {
			$this->error($exception->getMessage());
		} finally {
			if (isset($exception)) {
				unset($exception);
			}
		}
	}

}
