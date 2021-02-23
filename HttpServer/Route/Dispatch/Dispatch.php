<?php
declare(strict_types=1);


namespace HttpServer\Route\Dispatch;


use Closure;
use HttpServer\Controller;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Dispatch
 * @package HttpServer\Route\Dispatch
 */
class Dispatch
{

	/** @var Closure|array */
	protected array|Closure $handler;

	protected mixed $request;

	/**
	 * @param $handler
	 * @param $request
	 * @return static
	 * @throws ComponentException
	 */
	public static function create($handler, $request): static
	{
		$class = objectPool(get_called_class());
		$class->handler = $handler;
		$class->request = $request;
		if ($handler instanceof Closure) {
			$class->bind();
		}
		$class->bindParam();
		return $class;
	}


	/**
	 * @return mixed
	 * 执行函数
	 */
	public function dispatch(): mixed
	{
		return call_user_func($this->handler, ...$this->request);
	}


	/**
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	protected function bind()
	{
		$class = $this->bindRequest(Snowflake::createObject(Controller::class));
		$this->handler = Closure::bind($this->handler, $class);
	}


	/**
	 * @param $controller
	 * @return mixed
	 */
	protected function bindRequest($controller): mixed
	{
		$controller->request = Context::getContext('request');
		$controller->headers = $controller->request?->headers;
		$controller->input = $controller->request?->params;
		return $controller;
	}


	/**
	 * 参数绑定
	 */
	protected function bindParam()
	{
		if ($this->handler instanceof Closure) {
			return;
		}
		$controller = $this->handler[0];
		$this->bindRequest($controller);
	}

}
