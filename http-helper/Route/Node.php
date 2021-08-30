<?php
declare(strict_types=1);


namespace Http\Route;


use Annotation\Aspect;
use Closure;
use Exception;
use Http\Exception\RequestException;
use JetBrains\PhpStorm\Pure;
use Kiri\Events\EventProvider;
use Kiri\Exception\NotFindClassException;
use Kiri\IAspect;
use Kiri\Kiri;
use ReflectionException;
use Server\Events\OnAfterWorkerStart;
use Server\RequestInterface;

/**
 * Class Node
 * @package Kiri\Kiri\Route
 */
class Node
{

	public string $path = '';
	public int $index = 0;

	/** @var string[] */
	public array $method = [];

	/** @var Node[] $childes */
	public array $childes = [];

	public array $group = [];

	private string $_error = '';

	private string $_dataType = '';

	/** @var array<string, array|Closure|null> */
	private array $_handler = [];

	public string $htmlSuffix = '.html';
	public bool $enableHtmlSuffix = false;
	public array $namespace = [];
	public array $middleware = [];

	public string $sourcePath = '';

	/** @var array|Closure */
	public Closure|array $callback = [];

	private string $_alias = '';


	/**
	 * @param string $dataType
	 */
	public function setDataType(string $dataType)
	{
		$this->_dataType = $dataType;
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function __construct()
	{
		$eventDispatcher = di(EventProvider::class);
		$eventDispatcher->on(OnAfterWorkerStart::class, [$this, 'setParameters']);
	}


	/**
	 * @param string $data
	 * @return mixed
	 */
	public function unpack(string $data): mixed
	{
		if ($this->_dataType == 'json') {
			return json_decode($data, true);
		}
		if ($this->_dataType == 'serializes') {
			return unserialize($data);
		}
		return $data;
	}


	/**
	 * @param string|array|Closure $handler
	 * @param string $method
	 * @param string $path
	 * @return Node
	 * @throws ReflectionException
	 */
	public function setHandler(string|array|Closure $handler, string $method, string $path): static
	{
		$this->sourcePath = '/' . ltrim($path, '/');
		if (is_string($handler) && str_contains($handler, '@')) {
			$handler = $this->splitHandler($handler);
		} else if ($handler != null && !is_callable($handler, true)) {
			$this->_error = 'Controller is con\'t exec.';
		}
		$this->_handler[$method] = $handler;
		return $this;
	}


	/**
	 * @param string $handler
	 * @return array
	 * @throws ReflectionException
	 */
	private function splitHandler(string $handler): array
	{
		list($controller, $action) = explode('@', $handler);
		if (!class_exists($controller) && !empty($this->namespace)) {
			$controller = implode('\\', $this->namespace) . '\\' . $controller;
		}
		return [Kiri::getDi()->get($controller), $action];
	}


	/**
	 * @param string $method
	 * @param $handler
	 * @param $_injectParameters
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function injectMiddleware(string $method, $handler, $_injectParameters): void
	{
		if (!($handler instanceof Closure)) {
			$callback = $this->injectControllerMiddleware($handler);
		} else {
			$callback = $this->injectClosureMiddleware($handler);
		}
		HandlerProviders::add($method, $this->sourcePath, $callback, $_injectParameters);
	}


	/**
	 * @param $handler
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function injectControllerMiddleware($handler): mixed
	{
		MiddlewareManager::addMiddlewares($handler[0], $handler[1], $this->middleware);
		return MiddlewareManager::callerMiddlewares(
			$handler[0], $handler[1], $this->aopHandler($this->getAop($handler), $handler)
		);
	}


	/**
	 * @param $handler
	 * @return Closure
	 */
	private function injectClosureMiddleware($handler): Closure
	{
		if (!empty($this->middleware)) {
			return MiddlewareManager::closureMiddlewares($this->middleware, $this->normalHandler($handler));
		} else {
			return $this->normalHandler($handler);
		}
	}


	private ?array $_injectParameters = [];


	/**
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function setParameters(): static
	{
		$container = Kiri::getDi();
		if (empty($this->_handler)) {
			return $this;
		}
		foreach ($this->_handler as $method => $dispatcher) {
			if ($dispatcher instanceof Closure) {
				$_injectParameters = $container->resolveFunctionParameters($dispatcher);
			} else {
				[$controller, $action] = $dispatcher;
				if (is_object($controller)) {
					$controller = get_class($controller);
				}
				$_injectParameters = $container->getMethodParameters($controller, $action);
			}
			$this->injectMiddleware($method, $dispatcher, $_injectParameters);
		}
		$this->_handler = [];
		return $this;
	}


	/**
	 * @param IAspect|null $reflect
	 * @param $handler
	 * @return Closure
	 */
	#[Pure] private function aopHandler(?IAspect $reflect, $handler): Closure
	{
		if (is_null($reflect)) {
			return $this->normalHandler($handler);
		}
		return static function () use ($reflect, $handler) {
			return $reflect->invoke($handler, func_get_args());
		};
	}


	/**
	 * @throws ReflectionException|NotFindClassException
	 */
	private function getAop($handler): ?IAspect
	{
		[$controller, $action] = $handler;

		if (is_object($controller)) {
			$controller = get_class($controller);
		}

		/** @var Aspect $aspect */
		$aspect = Kiri::getDi()->getMethodByAnnotation(Aspect::class, $controller, $action);
		if (empty($aspect)) {
			return null;
		}
		return di($aspect->aspect);
	}


	/**
	 * @param $handler
	 * @return Closure
	 */
	private function normalHandler($handler): Closure
	{
		return static function () use ($handler) {
			return call_user_func($handler, ...func_get_args());
		};
	}


	/**
	 * @return array
	 */
	#[Pure] protected function annotation(): array
	{
		return $this->getMiddleWares();
	}


	/**
	 * @param RequestInterface $request
	 * @return bool
	 */
	#[Pure] public function methodAllow(RequestInterface $request): bool
	{
		if (!in_array($request->getMethod(), $this->method)) {
			return true;
		}
		return $this->method == 'any';
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function checkSuffix(): bool
	{
		if ($this->enableHtmlSuffix) {
			$url = request()->getUri()->getPath();
			$nowLength = strlen($this->htmlSuffix);
			if (strpos($url, $this->htmlSuffix) !== strlen($url) - $nowLength) {
				return false;
			}
		}
		return true;
	}


	/**
	 * @return string
	 * 错误信息
	 */
	public function getError(): string
	{
		return $this->_error;
	}

	/**
	 * @param Node $node
	 * @return Node
	 */
	public function addChild(Node $node): Node
	{
		$this->childes[] = $node;
		return $node;
	}


	/**
	 * @param string $search
	 * @return Node|null
	 * @throws Exception
	 */
	public function findNode(string $search): ?Node
	{
		if (empty($this->childes)) {
			return null;
		}
		foreach ($this->childes as $val) {
			if ($search == $val->path) {
				return $val;
			}
		}
		return null;
	}


	/**
	 * @param string $alias
	 * @return $this
	 * 别称
	 */
	public function alias(string $alias): static
	{
		$this->_alias = $alias;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getAlias(): string
	{
		return $this->_alias;
	}


	/**
	 * @param Closure|array $class
	 * @return $this
	 */
	public function addMiddleware(Closure|array $class): static
	{
		if (empty($class)) return $this;
		foreach ($class as $closure) {
			if (in_array($closure, $this->middleware)) {
				continue;
			}
			$this->middleware[] = $closure;
		}
		return $this;
	}


	/**
	 * @return array
	 */
	public function getMiddleWares(): array
	{
		return $this->middleware;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function dispatch(): mixed
	{
		if (!in_array(request()->getMethod(), $this->method)) {
			throw new RequestException('<h2>HTTP 405 Method allow</h2><hr><i>Powered by Swoole</i>', 405);
		}
		$handlerProviders = HandlerProviders::get($this->sourcePath, request()->getMethod());
		if (empty($handlerProviders)) {
			throw new RequestException('<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>', 404);
		}
		if (!empty($handlerProviders[1])) {
			var_dump($handlerProviders[1]);
			return call_user_func($handlerProviders[0], ...$handlerProviders[1]);
		}
		return call_user_func($handlerProviders[0]);
	}

}
