<?php
declare(strict_types=1);


namespace Http\Route;


use Closure;
use Exception;
use Http\Context\Context;
use Http\Exception\RequestException;
use JetBrains\PhpStorm\Pure;
use Kiri\Events\EventProvider;
use Kiri\Exception\NotFindClassException;
use Kiri\Kiri;
use ReflectionException;
use Server\Constant;
use Server\Constrict\RequestInterface;
use Server\Events\OnAfterWorkerStart;

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

	/** @var array<string,mixed> */
	public array $namespace = [];

	/** @var array<string,mixed> */
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
     * @param \Http\Route\Router $router
     */
	public function __construct(public Router $router)
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
			return $this;
		}
		$this->_handler[$method] = [$handler, $this->resolveMethodParams($handler)];
		return $this;
	}


	/**
	 * @param $dispatcher
	 * @return array
	 * @throws \ReflectionException
	 */
	private function resolveMethodParams($dispatcher): array
	{
		$container = Kiri::getDi();
		if ($dispatcher instanceof Closure) {
			$_injectParameters = $container->getFunctionParameters($dispatcher);
		} else {
			$_injectParameters = $container->getMethodParameters(
				$dispatcher[0]::class, $dispatcher[1]);
		}
		return $_injectParameters;
	}


	/**
	 * @param string $handler
	 * @return array
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
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function injectMiddleware(string $method, $handler, $_injectParameters): void
	{
		$callback = (new Pipeline())->overall($this->router->getMiddleware())
			->through($this->middleware[$method] ?? [])
			->through(MiddlewareManager::get($handler))
			->send($_injectParameters)
			->then($handler);

		HandlerProviders::add($method, $this->sourcePath, $callback);
	}


	/**
	 * @throws ReflectionException
	 */
	public function setParameters(): static
	{
		if (empty($this->_handler)) {
			return $this;
		}
		foreach ($this->_handler as $method => $dispatcher) {
			$this->injectMiddleware($method, ...$dispatcher);
		}
		$this->_handler = [];
		return $this;
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
	public function methodAllow(RequestInterface $request): bool
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
		$this->childes[$node->path] = $node;
		return $node;
	}


	/**
	 * @param string $search
	 * @return Node|null
	 * @throws Exception
	 */
	public function findNode(string $search): ?Node
	{
		return $this->childes[$search] ?? null;
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
	 * @param $method
	 * @param Closure|array $class
	 * @return $this
	 */
	public function addMiddleware($method, Closure|array $class): static
	{
		if (empty($class)) return $this;
		if (!isset($this->middleware[$method])) {
			$this->middleware[$method] = [];
		}
		foreach ($class as $closure) {
			if (in_array($closure, $this->middleware[$method])) {
				continue;
			}
			$this->middleware[$method][] = $closure;
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
	 * @param RequestInterface $request
	 * @return mixed
	 * @throws RequestException
	 * @throws Exception
	 */
	public function dispatch(RequestInterface $request): mixed
	{
		if (!in_array($request->getMethod(), $this->method)) {
			throw new RequestException(Constant::STATUS_405_MESSAGE, 405);
		}
		$handlerProviders = HandlerProviders::get($this->sourcePath, $request->getMethod());
		if (empty($handlerProviders)) {
			throw new RequestException(Constant::STATUS_404_MESSAGE, 404);
		}
		return $handlerProviders->interpreter($request);
	}

}
