<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Closure;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Request;
use Exception;

use HttpServer\IInterface\After;
use HttpServer\IInterface\Interceptor;
use HttpServer\IInterface\Limits;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Throwable;
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

	public array $rules = [];

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
	 * @param $handler
	 * @return Node
	 * @throws
	 */
	public function bindHandler($handler): static
	{
		if ($handler instanceof Closure) {
			$this->handler = $handler;
		} else if (is_string($handler) && str_contains($handler, '@')) {
			list($controller, $action) = explode('@', $handler);
			if (!empty($this->namespace)) {
				$controller = implode('\\', $this->namespace) . '\\' . $controller;
			}
			$this->handler = $this->getReflect($controller, $action);
		} else if ($handler != null && !is_callable($handler, true)) {
			$this->_error = 'Controller is con\'t exec.';
		} else {
			$this->handler = $handler;
		}
		return $this;
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
	 */
	public function afterDispatch($response = null): mixed
	{
		return (Reduce::after($this->_after))(\request(), $response);
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
		return $this->checkRule();
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	private function checkRule(): bool
	{
		if (empty($this->rules)) {
			return true;
		}
		foreach ($this->rules as $rule) {
			if (!isset($rule['class'])) {
				$rule['class'] = Filter::class;
			}
			/** @var Filter $object */
			$object = Snowflake::createObject($rule);
			if (!$object->handler()) {
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
			return [$reflect->newInstance(), $action];
		} catch (Throwable $exception) {
			$this->_error = $exception->getMessage();
			$this->error($exception, 'router');
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
		/** @var Node $oLod */
		$oLod = $this->childes[$field] ?? null;
		if (!empty($oLod)) {
			$node = $oLod;
		}
		$this->childes[$field] = $node;
		return $this->childes[$field];
	}

	/**
	 * @param $rule
	 * @return $this
	 */
	public function filter($rule): static
	{
		if (empty($rule)) {
			return $this;
		}
		if (!isset($rule[0])) {
			$rule = [$rule];
		}
		foreach ($rule as $value) {
			if (empty($value)) {
				continue;
			}
			$this->rules[] = $value;
		}
		return $this;
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
			if (preg_match($_searchMatch, $key, $match)) {
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
	 * @param int $limit
	 * @param int $duration
	 * @param bool $isBindConsumer
	 * @return $this
	 * @throws Exception
	 */
	public function limits(int $limit, int $duration = 60, bool $isBindConsumer = false): static
	{
		$limits = Snowflake::app()->getLimits();
		$limits->addLimits($this->path, $limit, $duration, $isBindConsumer);
		return $this;
	}


	/**
	 * @param array|Closure|string $class
	 * @return Node
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function addMiddleware(Closure|string|array $class): static
	{
		if (empty($class)) return $this;
		if (is_string($class)) {
			$class = $this->resolve_aop($class);
			if ($class === null) {
				return $this;
			}
		}
		if (is_array($class)) {
			if (isset($class[0]) && is_object($class[0])) {
				$class = [$class];
			}
		} else {
			$class = [$class];
		}
		foreach ($class as $closure) {
			if (is_string($closure)) {
				$closure = [Snowflake::createObject($closure), 'onHandler'];
			}
			if (in_array($closure, $this->middleware)) {
				continue;
			}
			$this->middleware[] = $closure;
		}
		return $this;
	}


	/**
	 * @param string $class
	 * @return array|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolve_aop(string $class): array|null
	{
		$class = Snowflake::createObject($class);
		if ($class instanceof \HttpServer\IInterface\Middleware) {
			return [$class, 'onHandler'];
		} else if ($class instanceof Interceptor) {
			return [$class, 'Interceptor'];
		} else if ($class instanceof After) {
			return [$class, 'onHandler'];
		} else if ($class instanceof Limits) {
			return [$class, 'next'];
		} else {
			return null;
		}
	}


	/**
	 * @return array
	 */
	public function getMiddleWares(): array
	{
		return $this->middleware;
	}


	/**
	 * @throws Exception
	 */
	public function restructure(): static
	{
		if (empty($this->handler)) {
			return $this;
		}
		/** @var Middleware $made */
		$made = Snowflake::createObject(Middleware::class);
		$made->getGenerate($this);
		return $this;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function dispatch(): mixed
	{
//		if (!empty($this->callback)) {
//			return $this->runWith(...func_get_args());
//		}
		if (empty($this->restructure()->callback)) {
			var_dump('404');
			return Json::to(404, $this->errorMsg());
		}
		return $this->runWith(...func_get_args());
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	private function runWith(): mixed
	{
		$requestParams = func_get_args();
		if (func_num_args() > 0) {
			return call_user_func($this->callback, ...$requestParams);
		} else {
			return call_user_func($this->callback, \request());
		}
	}


	/**
	 * @return string
	 */
	private function errorMsg(): string
	{
		return $this->_error ?? 'Page not found.';
	}

}
