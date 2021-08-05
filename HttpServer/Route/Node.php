<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Annotation\Aspect;
use Annotation\Route\RpcProducer;
use Closure;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Request;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\IAspect;
use Snowflake\Snowflake;

/**
 * Class Node
 * @package Snowflake\Snowflake\Route
 */
class Node extends HttpService
{

	public string $path = '';
	public int $index = 0;
	public string $method = '';

	/** @var Node[] $childes */
	public array $childes = [];

	public array $group = [];

	private string $_error = '';

	private string $_dataType = '';

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
	 * @param string $data
	 * @return mixed
	 */
	public function unpack(string $data): mixed
	{
		if ($this->_dataType == RpcProducer::PROTOCOL_JSON) {
			return json_decode($data, true);
		}
		if ($this->_dataType == RpcProducer::PROTOCOL_SERIALIZE) {
			return unserialize($data);
		}
		return $data;
	}


	/**
	 * @param $handler
	 * @param $path
	 * @return Node
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function setHandler($handler, $path): static
	{
		$this->sourcePath = $path;
		if (is_string($handler) && str_contains($handler, '@')) {
			$handler = $this->splitHandler($handler);
		} else if ($handler != null && !is_callable($handler, true)) {
			$this->_error = 'Controller is con\'t exec.';
		}
		return $this->setParameters($handler);
	}


	/**
	 * @param string $handler
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function splitHandler(string $handler): array
	{
		list($controller, $action) = explode('@', $handler);
		if (!class_exists($controller) && !empty($this->namespace)) {
			$controller = implode('\\', $this->namespace) . '\\' . $controller;
		}
		return [Snowflake::getDi()->get($controller), $action];
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function injectMiddleware($handler): static
	{
		$manager = di(MiddlewareManager::class);
		if (!($handler instanceof Closure)) {
			$callback = $this->injectControllerMiddleware($manager, $handler);
		} else {
			$callback = $this->injectClosureMiddleware($manager, $handler);
		}
		$this->getHandlerProviders()->add($this->method, $this->sourcePath, $callback);
		return $this;
	}


	/**
	 * @return HandlerProviders
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function getHandlerProviders(): HandlerProviders
	{
		return Snowflake::getDi()->get(HandlerProviders::class);
	}


	/**
	 * @param $manager
	 * @param $handler
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function injectControllerMiddleware($manager, $handler): mixed
	{
		$manager->addMiddlewares($handler[0], $handler[1], $this->middleware);
		return $manager->callerMiddlewares(
			$handler[0], $handler[1], $this->aopHandler($this->getAop($handler), $handler)
		);
	}


	/**
	 * @param $manager
	 * @param $handler
	 * @return mixed
	 */
	private function injectClosureMiddleware($manager, $handler): mixed
	{
		if (!empty($this->middleware)) {
			return $manager->closureMiddlewares($this->middleware, $this->normalHandler($handler));
		} else {
			return $this->normalHandler($handler);
		}
	}


	private ?array $_injectParameters = [];


	/**
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function setParameters($handler): static
	{
		$container = Snowflake::getDi();
		if ($handler instanceof Closure) {
			$this->_injectParameters = $container->resolveFunctionParameters($handler);
		} else {
			[$controller, $action] = $handler;
			if (is_object($controller)) {
				$controller = get_class($controller);
			}
			$this->_injectParameters = $container->getMethodParameters($controller, $action);
		}
		return $this->injectMiddleware($handler);
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

		$params = $this->_injectParameters;
		return static function () use ($reflect, $handler, $params) {
			return $reflect->invoke($handler, $params);
		};
	}


	/**
	 * @throws ReflectionException|NotFindClassException
	 */
	private function getAop($handler): ?IAspect
	{
		[$controller, $action] = $handler;
		$aspect = Snowflake::getDi()->getMethodAttribute($controller::class, $action);
		if (empty($aspect)) {
			return null;
		}
		foreach ($aspect as $value) {
			if ($value instanceof Aspect) {
				return di($value->aspect);
			}
		}
		return null;
	}


	/**
	 * @param $handler
	 * @return Closure
	 */
	private function normalHandler($handler): Closure
	{
		$params = $this->_injectParameters;
		return static function () use ($handler, $params) {
			return call_user_func($handler, ...$params);
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
	 * @param Request $request
	 * @return bool
	 */
	public function methodAllow(Request $request): bool
	{
		if ($this->method == $request->getMethod()) {
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
			$url = request()->getUri();
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
		$handlerProviders = $this->getHandlerProviders()->get($this->sourcePath, $this->method);
		if (empty($handlerProviders)) {
			return '<h2>HTTP 404 Not Found</h2><hr><i>Powered by Swoole</i>';
		}
		var_dump($handlerProviders);
		return call_user_func($handlerProviders, \request());
	}

}
