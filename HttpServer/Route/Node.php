<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Annotation\Route\RpcProducer;
use Closure;
use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;
use Snowflake\Aop;
use Snowflake\Core\Json;
use Snowflake\Exception\NotFindClassException;
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

	/** @var ?Closure|?array */
	public Closure|array|null $handler;
	public string $htmlSuffix = '.html';
	public bool $enableHtmlSuffix = false;
	public array $namespace = [];
	public array $middleware = [];

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
	 * @return Node
	 * @throws
	 */
	public function bindHandler($handler): static
	{
		if (is_string($handler) && str_contains($handler, '@')) {
			list($controller, $action) = explode('@', $handler);
			if (!class_exists($controller) && !empty($this->namespace)) {
				$controller = implode('\\', $this->namespace) . '\\' . $controller;
			}
			$this->handler = [Snowflake::getDi()->get($controller), $action];
		} else if ($handler != null && !is_callable($handler, true)) {
			$this->_error = 'Controller is con\'t exec.';
		} else {
			$this->handler = $handler;
		}
		return $this->injectMiddleware();
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function injectMiddleware(): static
	{
		$manager = di(MiddlewareManager::class);
		if ($this->handler instanceof Closure) {
			if (!empty($this->middleware)) {
				$this->callback = $manager->closureMiddlewares($this->middleware, $this->createDispatch());
			} else {
				$this->callback = $this->createDispatch();
			}
		} else {
			$manager->addMiddlewares($this->handler[0], $this->handler[1], $this->middleware);
			$this->callback = $manager->callerMiddlewares(
				$this->handler[0], $this->handler[1], $this->createDispatch()
			);
		}
		return $this;
	}


	/**
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function createDispatch(): Closure
	{
		$application = $this;

		/** @var Aop $aop */
		$aop = Snowflake::app()->get('aop');
		if ($this->handler instanceof Closure || !$aop->hasAop($this->handler)) {
			return $this->normalHandler($application);
		} else {
			return $this->aopHandler($aop->getAop($this->handler), $application);
		}
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $application
	 * @return Closure
	 * @throws ReflectionException|NotFindClassException
	 */
	private function aopHandler(ReflectionClass $reflect, $application): Closure
	{
		$callback = [$reflect->getMethod('invoke'), 'invokeArgs'];

		$instance = Snowflake::getDi()->get($reflect->getName());
		return static function () use ($callback, $application, $instance, $reflect) {
			call_user_func($callback, $instance);
		};
	}


	/**
	 * @param $application
	 * @return Closure
	 */
	private function normalHandler($application): Closure
	{
		return static function () use ($application) {
			return call_user_func($application->handler);
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
	 * @throws Exception
	 */
	public function findNode(string $search): ?Node
	{
		if (empty($this->childes)) {
			return null;
		}
		if (isset($this->childes[$search])) {
			return $this->childes[$search];
		}
		foreach ($this->childes as $key => $val) {
			if ($search == $key) {
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
		return call_user_func($this->callback, \request());
	}


	/**
	 * @return string
	 */
	private function errorMsg(): string
	{
		return $this->_error ?? 'Page not found.';
	}

}
