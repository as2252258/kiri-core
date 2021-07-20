<?php
declare(strict_types=1);

namespace HttpServer\Route;

use Closure;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Controller;
use HttpServer\Exception\RequestException;
use HttpServer\Http\Request;
use HttpServer\IInterface\Middleware;
use HttpServer\IInterface\RouterInterface;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Rpc\Actuator;
use Snowflake\Abstracts\Config;
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
    public static array $nodes = [];
    public array $groupTacks = [];
    public ?string $dir = 'App\\Http\\Controllers';

    const NOT_FOUND = 'Page not found or method not allowed.';

    /** @var string[] */
    public array $methods = ['get', 'post', 'options', 'put', 'delete', 'receive', 'head'];


    public ?Closure $middleware = null;

    public int $useTree = ROUTER_TREE;


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
        $this->dir = Config::get('http.namespace', $this->dir);
    }


    /**
     * @param bool $useTree
     */
    public function setUseTree(bool $useTree): void
    {
        $this->useTree = $useTree ? ROUTER_TREE : ROUTER_HASH;
    }


    /**
     * @param $port
     * @param Closure|array|string $closure
     * @param null $method
     * @return Node|bool|null
     * @throws
     */
    public function addPortListen($port, Closure|array|string $closure, $method = null): Node|null|bool
    {
        if (!is_string($closure)) {
            return $this->addRoute('add-port-listen/port_' . $port, $closure, 'listen');
        }
        if (empty($method)) {
            return $this->addError($closure . '::' . $method);
        }
        $_closure = Snowflake::createObject($closure);
        if (!method_exists($_closure, $method)) {
            return $this->addError($closure . '::' . $method);
        }
        return $this->addRoute('add-port-listen/port_' . $port, [$_closure, $method], 'listen');
    }


    /**
     * @param $path
     * @param $handler
     * @param string $method
     * @return ?Node
     * @throws Exception
     */
    public function addRoute($path, $handler, string $method = 'any'): ?Node
    {
        $method = strtolower($method);
        if (!isset(static::$nodes[$method])) {
            static::$nodes[$method] = [];
        }

        if ($handler instanceof Closure) {
            $handler = Closure::bind($handler, new Controller());
        }

//        if ($this->useTree === ROUTER_TREE) {
//            return $this->tree($path, $handler, $method);
//        }

        return $this->hash($path, $handler, $method);
    }


    /**
     * @param $path
     * @param $handler
     * @param string $method
     * @return ?Node
     */
    private function hash($path, $handler, string $method = 'any'): ?Node
    {
        $path = $this->resolve($path);

        static::$nodes[$method][$path] = $this->NodeInstance($path, 0, $method);

        return static::$nodes[$method][$path]->bindHandler($handler);
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
        list($first, $explode) = $this->split($path);

        $parent = static::$nodes[$method][$first] ?? null;
        if (empty($parent)) {
            static::$nodes[$method][$first] = $parent = $this->NodeInstance('/', 0, $method);
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
     * @throws Exception
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
	 * @param $port
	 * @param callable $callback
	 * @return mixed
	 * @throws Exception
	 */
    public function addRpcService($port, callable $callback): mixed
    {
        return call_user_func($callback, new Actuator($port));
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
     * @param $route
     * @param $handler
     * @return Any
     * @throws
     */
    public function any($route, $handler): Any
    {
        $nodes = [];
        foreach (['get', 'post', 'options', 'put', 'delete', 'head'] as $method) {
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
     * @throws Exception
     */
    public function head($route, $handler): ?Node
    {
        return $this->addRoute($route, $handler, 'head');
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
     * @param int $index
     * @param string $method
     * @return Node
     * @throws
     */
    public function NodeInstance($value, int $index = 0, string $method = 'get'): Node
    {
        $node = new Node();
        $node->childes = [];
        $node->path = $value;
        $node->index = $index;
        $node->method = $method;
        $node->namespace = $this->loadNamespace($method);

        $name = array_column($this->groupTacks, 'middleware');
        if ($this->middleware instanceof \Closure) {
            $node->addMiddleware([$this->middleware]);
        }

        if (is_array($name)) {
            $node->addMiddleware($this->resolve_middleware($name));
        }

        return $node;
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
        return $array;
    }


    /**
     * @param $value
     * @return Closure|array|null
     * @throws NotFindClassException
     * @throws ReflectionException
     */
    private function getMiddlewareInstance($value): null|Closure|array
    {
        if (is_string($value)) {
            $value = Snowflake::createObject($value);
            if (!($value instanceof Middleware)) {
                return null;
            }
            return [$value, 'onHandler'];
        } else {
            return $value;
        }
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
            return static::$nodes[$method]['/'] ?? null;
        }
        $first = array_shift($explode);
        if (!($parent = static::$nodes[$method][$first] ?? null)) {
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
        foreach (static::$nodes as $node) {
            /** @var Node[] $node */
            foreach ($node as $path => $_node) {
                $paths[] = strtoupper($_node->method) . ' : ' . $path;
            }
        }
        return $paths;
    }


    /**
     * @return mixed
     * @throws
     */
    public function dispatch(): mixed
    {
        $node = $this->find_path(\request());
        if (!($node instanceof Node)) {
            throw new RequestException(\request()->getUri() . ' -> ' . self::NOT_FOUND, 404);
        }
        send(($response = $node->dispatch()), 200);
        if (!$node->hasAfter()) {
            return null;
        }
        return $node->afterDispatch($response);
    }


    /**
     * @param $exception
     * @return mixed
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
     */
    public function find_path(Request $request): ?Node
    {
//        return $this->Branch_search($request);
        $method = $request->getMethod();
        $uri = $request->headers->get('request_uri', '/');

        if (!isset(static::$nodes[$method])) {
            return null;
        }
        $methods = static::$nodes[$method];
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
        if (!isset(static::$nodes[$method])) {
            return null;
        }
        $methods = static::$nodes[$method];
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
        if (!isset(static::$nodes[$method])) {
            return null;
        }
        if (!isset(static::$nodes[$method]['*'])) {
            return null;
        }
        return static::$nodes[$method]['*'];
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
            $router = $this;
            include_once "{$files}";
        } catch (\Throwable $exception) {
            $this->addError($exception, 'throwable');
        } finally {
            if (isset($exception)) {
                unset($exception);
            }
        }
    }

}
