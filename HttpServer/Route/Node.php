<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Closure;
use HttpServer\Http\Request;
use Exception;
use HttpServer\Application;
use HttpServer\Route\Annotation\Annotation;
use Snowflake\Core\JSON;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;

/**
 * Class Node
 * @package Snowflake\Snowflake\Route
 */
class Node extends Application
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
	public $handler;
	public string $htmlSuffix = '.html';
	public bool $enableHtmlSuffix = false;
	public array $namespace = [];
	public array $middleware = [];

	/** @var array|Closure  */
	public $callback = [];

	private string $_alias = '';

	private array $_interceptors = [];
	private array $_after = [];
	private array $_limits = [];

	/**
	 * @param $handler
	 * @return Node
	 * @throws
	 */
	public function bindHandler($handler)
	{
		if ($handler instanceof Closure) {
			$this->handler = $handler;
		} else if (is_string($handler) && strpos($handler, '@') !== false) {
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
	public function hasInterceptor()
	{
		return count($this->_interceptors) > 0;
	}


	/**
	 * @return bool
	 */
	public function hasLimits()
	{
		return count($this->_limits) > 0;
	}


	/**
	 * @param $response
	 * @return mixed|null
	 */
	public function afterDispatch($response = null)
	{
		return Coroutine::create(function ($request, $response) {
			(Reduce::after($this->_after))($request, $response);
		}, \request(), $response);
	}


	/**
	 * @return array
	 */
	public function getInterceptor()
	{
		return $this->_interceptors;
	}


	/**
	 * @return array
	 */
	public function getAfters()
	{
		return $this->_after;
	}


	/**
	 * @return bool
	 */
	public function hasAfter()
	{
		return count($this->_after) > 0;
	}


	/**
	 * @return array
	 */
	public function getLimits()
	{
		return $this->_limits;
	}

	/**
	 * @param $request
	 * @return bool
	 */
	public function methodAllow(Request $request)
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
	public function checkSuffix()
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
	private function checkRule()
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
			};
		}
		return true;
	}

	/**
	 * @param string $controller
	 * @param string $action
	 * @return null|array
	 * @throws Exception
	 */
	private function getReflect(string $controller, string $action)
	{
		try {
			$reflect = Snowflake::getDi()->getReflect($controller);
			if (!$reflect->isInstantiable()) {
				throw new Exception($controller . ' Class is con\'t Instantiable.');
			}

			if (!empty($action) && !$reflect->hasMethod($action)) {
				throw new Exception('method ' . $action . ' not exists at ' . $controller . '.');
			}

			/** @var Annotation $annotation */
			$annotation = Snowflake::app()->annotation->get('http');
			if (!empty($annotations = $annotation->getAnnotation(Annotation::class))) {
				$annotation->read($this, $reflect, $action, $annotations);
			}
			return [$reflect->newInstance(), $action];
		} catch (\Throwable $exception) {
			$this->_error = $exception->getMessage();
			$this->error($exception->getMessage(), 'router');
			return null;
		}
	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addInterceptor($handler)
	{
		$this->_interceptors[] = $handler;

	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addAfter($handler)
	{
		$this->_after[] = $handler;
	}


	/**
	 * @param Closure|array|string $handler
	 * @throws Exception
	 */
	public function addLimits($handler)
	{
		$this->_limits[] = $handler;

	}


	/**
	 * @return string
	 * 错误信息
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * @param Node $node
	 * @param string $field
	 * @return Node
	 */
	public function addChild(Node $node, string $field)
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
	public function filter($rule)
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
	 * @return Node|mixed
	 */
	public function findNode(string $search)
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
				\Input()->addGetParam($match[1] ?? '--', $search);
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
	public function alias(string $alias)
	{
		$this->_alias = $alias;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getAlias()
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
	public function limits(int $limit, int $duration = 60, bool $isBindConsumer = false)
	{
		$limits = Snowflake::app()->getLimits();
		$limits->addLimits($this->path, $limit, $duration, $isBindConsumer);
		return $this;
	}


	/**
	 * @param $middles
	 * @throws
	 */
	public function bindMiddleware(array $middles)
	{
		$_tmp = [];
		if (empty($middles)) {
			return;
		}
		$this->middleware = $this->each($middles, $_tmp);
	}


	/**
	 * @param string|\Closure $class
	 * @throws Exception
	 */
	public function addMiddleware($class)
	{
		if (!is_callable($class, true)) {
			return;
		}
		if (is_string($class)) {
			$class = Snowflake::createObject($class);
			if (!($class instanceof \HttpServer\IInterface\Middleware)) {
				return;
			}
			$class = [$class, 'handler'];
		}
		$this->middleware[] = $class;
	}


	/**
	 * @throws Exception
	 */
	private function restructure()
	{
		if (empty($this->handler)) {
			return $this;
		}
		$made = Snowflake::createObject(Middleware::class);
		$made->setMiddleWares($this->middleware);
		$made->getGenerate($this);
		return $this;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function dispatch()
	{
		$node = $this->restructure();
		if (empty($node->callback)) {
			return JSON::to(404, $node->_error ?? 'Page not found.');
		}
		return call_user_func($node->callback, \request());
	}


	/**
	 * @param $array
	 * @param $_temp
	 * @return array
	 * @throws Exception
	 */
	private function each($array, $_temp)
	{
		if (!is_array($array)) {
			return $_temp;
		}
		foreach ($array as $class) {
			if (is_array($class)) {
				$_temp = $this->each($class, $_temp);
				continue;
			}

			if (!class_exists($class)) {
				continue;
			}
			$_temp[] = Snowflake::createObject($class);
		}
		return $_temp;
	}
}
