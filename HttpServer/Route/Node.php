<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Annotation\Route\After;
use Annotation\Route\Interceptor;
use Annotation\Route\Limits;
use Annotation\Route\Middleware;
use Annotation\Route\RpcProducer;
use Closure;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Controller;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\HttpFilter;
use JetBrains\PhpStorm\Pure;
use Snowflake\Core\Json;
use Snowflake\Snowflake;
use Throwable;
use validator\Validator;
use function Input;

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

	private array $rules = [];


	private string $_dataType = '';

	/** @var ?Closure|?array */
	public Closure|array|null $handler;
	public string $htmlSuffix = '.html';
	public bool $enableHtmlSuffix = false;
	public array $namespace = [];
	public array $middleware = [];

	/** @var array|Closure */
	public Closure|array $callback = [];

	private string $_alias = '';

	private array $_interceptors = [];
	private array $_after = [];
	private array $_limits = [];


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
	 * @return Node
	 * @throws
	 */
	public function bindHandler($handler): static
	{
		if (is_string($handler) && str_contains($handler, '@')) {
			list($controller, $action) = explode('@', $handler);
			if (!empty($this->namespace)) {
				$controller = implode('\\', $this->namespace) . '\\' . $controller;
			}
			$this->handler = $this->getReflect($controller, $action);
		} else if ($handler != null && !is_callable($handler, true)) {
			$this->_error = 'Controller is con\'t exec.';
		} else if ($handler instanceof Closure) {
			$this->handler = $handler;
		} else {
			[$controller, $action] = $this->handler = $handler;
			if (!($controller instanceof Controller)) {
				return $this;
			}
			$this->annotationInject($controller::class, $action);
		}
		if (!empty($this->handler)) {
			$this->callback = Reduce::reduce($this->createDispatch(), $this->annotation());
		}
		return $this;
	}


	/**
	 * @return Closure
	 */
	public function createDispatch(): Closure
	{
		return function () {
			$dispatchParam = Context::getContext('dispatch-param');
			if (empty($dispatchParam)) {
				$dispatchParam = [\request()];
			}
//            return call_user_func($this->handler, ...$dispatchParam);

			return \aop($this->handler, $dispatchParam);
		};
	}


	/**
	 * @return array
	 */
	protected function annotation(): array
	{
		$middleWares = $this->getMiddleWares();
		$middleWares = $this->annotation_limit($this, $middleWares);
		$middleWares = $this->annotation_interceptor($this, $middleWares);
		return $middleWares;
	}


	/**
	 * @param Node $node
	 * @param $middleWares
	 * @return array
	 */
	protected function annotation_interceptor(Node $node, $middleWares = []): array
	{
		if (!$node->hasInterceptor()) {
			return $middleWares;
		}
		foreach ($node->getInterceptor() as $item) {
			$middleWares[] = $item;
		}
		return $middleWares;
	}


	/**
	 * @param Node $node
	 * @param $middleWares
	 * @return array
	 */
	protected function annotation_limit(Node $node, $middleWares = []): array
	{
		if (!$node->hasLimits()) {
			return $middleWares;
		}
		foreach ($node->getLimits() as $item) {
			$middleWares[] = $item;
		}
		return $middleWares;
	}


	/**
	 * @return bool
	 */
	#[Pure] public function hasInterceptor(): bool
	{
		return count($this->_interceptors) > 0;
	}


	/**
	 * @return bool
	 */
	#[Pure] public function hasLimits(): bool
	{
		return count($this->_limits) > 0;
	}


	/**
	 * @param null $response
	 * @return mixed
	 * @throws Exception
	 */
	public function afterDispatch($response = null): mixed
	{
		if (is_object($this->_after[0])) {
			return call_user_func($this->_after, \request(), $response);
		}
		foreach ($this->_after as $value) {
			call_user_func($value, \request(), $response);
		}
		return $this->_after;
	}


	/**
	 * @return array
	 */
	public function getInterceptor(): array
	{
		return $this->_interceptors;
	}


	/**
	 * @return array
	 */
	public function getAfters(): array
	{
		return $this->_after;
	}


	/**
	 * @return bool
	 */
	#[Pure] public function hasAfter(): bool
	{
		return count($this->_after) > 0;
	}


	/**
	 * @return array
	 */
	public function getLimits(): array
	{
		return $this->_limits;
	}

	/**
	 * @param $request
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
	 * @param string $controller
	 * @param string $action
	 * @return null|array
	 * @throws Exception
	 */
	private function getReflect(string $controller, string $action): ?array
	{
		try {
			$reflect = Snowflake::getDi()->getReflect($controller);
			if (empty($reflect)) {
				throw new Exception($controller . ' Class is con\'t Instantiable.');
			}
			if (!empty($action) && !$reflect->hasMethod($action)) {
				throw new Exception('method ' . $action . ' not exists at ' . $controller . '.');
			}

			$this->annotationInject($reflect->getName(), $action);

			return [$reflect->newInstance(), $action];
		} catch (Throwable $exception) {
			$this->_error = $exception->getMessage();
			$this->addError($exception, 'router');
			return null;
		}
	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addInterceptor(Closure|string|array $handler)
	{
		if (!is_array($handler) || is_object($handler[0])) {
			$handler = [$handler];
		}
		foreach ($handler as $closure) {
			if (in_array($closure, $this->_interceptors)) {
				continue;
			}
			$this->_interceptors[] = $closure;
		}
	}


	/**
	 * @param string $className
	 * @param string $action
	 * @return Node
	 * @throws Exception
	 */
	public function annotationInject(string $className, string $action): Node
	{
		$annotation = annotation()->getMethods($className, $action);
		if (empty($annotation)) {
			return $this->injectRules($className, $action);
		}
		foreach ($annotation as $attribute) {
			if ($attribute instanceof Interceptor) {
				$this->addInterceptor($attribute->interceptor);
			}
			if ($attribute instanceof After) {
				$this->addAfter($attribute->after);
			}
			if ($attribute instanceof Middleware) {
				$this->addMiddleware($attribute->middleware);
			}
			if ($attribute instanceof Limits) {
				$this->addLimits($attribute->limits);
			}
		}
		return $this->injectRules($className, $action);
	}


	/**
	 * @param string $controller
	 * @param string $action
	 * @return $this
	 * @throws \Exception
	 */
	private function injectRules(string $controller, string $action): static
	{
		/** @var HttpFilter $filter */
		$filter = Snowflake::app()->get('filter');
		$this->rules = $filter->getRules($controller, $action);

		return $this;
	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addAfter(Closure|string|array $handler)
	{
		if (!is_array($handler) || is_object($handler[0])) {
			$handler = [$handler];
		}
		foreach ($handler as $closure) {
			if (in_array($closure, $this->_after)) {
				continue;
			}
			$this->_after[] = $closure;
		}
	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addLimits(Closure|string|array $handler)
	{
		if (!is_array($handler) || is_object($handler[0])) {
			$handler = [$handler];
		}
		foreach ($handler as $closure) {
			if (in_array($closure, $this->_limits)) {
				continue;
			}
			$this->_limits[] = $closure;
		}
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
	 * @param string $field
	 * @return Node
	 */
	public function addChild(Node $node, string $field): Node
	{
		$field = (string)$field;
		/** @var Node $oLod */
		$oLod = $this->childes[$field] ?? null;
		if (!empty($oLod)) {
			$node = $oLod;
		}
		$this->childes[$field] = $node;
		return $this->childes[$field];
	}


	/**
	 * @param string $search
	 * @return Node|null
	 */
	public function findNode(string $search): ?Node
	{
		if (empty($this->childes)) {
			return null;
		}

		if (isset($this->childes[$search])) {
			return $this->childes[$search];
		}

		$_searchMatch = '/<(\w+)?:(.+)?>/';
		foreach ($this->childes as $key => $val) {
			if (preg_match($_searchMatch, (string)$key, $match)) {
				Input()->addGetParam($match[1] ?? '--', $search);
				return $this->childes[$key];
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
		Context::setContext('dispatch-param', func_get_args());
		if (empty($this->callback)) {
			return Json::to(404, $this->errorMsg());
		}
		return call_user_func($this->handler, \request());
	}


	/**
	 * @return string
	 */
	private function errorMsg(): string
	{
		return $this->_error ?? 'Page not found.';
	}

}
