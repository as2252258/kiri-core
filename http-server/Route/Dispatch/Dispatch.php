<?php


namespace HttpServer\Route\Dispatch;


use HttpServer\Controller;

/**
 * Class Dispatch
 * @package HttpServer\Route\Dispatch
 */
class Dispatch
{

	protected $handler;

	protected $request;

	/**
	 * @param $handler
	 * @param $request
	 * @return static
	 */
	public static function create($handler, $request)
	{
		$class = new static();
		$class->handler = $handler;
		$class->request = $request;
		if ($handler instanceof \Closure) {
			$class->bind();
		}
		$class->bindParam();
		return $class;
	}


	/**
	 * @return mixed
	 * 执行函数
	 */
	public function dispatch()
	{
		return call_user_func($this->handler, $this->request);
	}


	/**
	 * 设置作用域
	 */
	protected function bind()
	{
		$this->handler = \Closure::bind($this->handler, new Controller());
	}


	/**
	 * 参数绑定
	 */
	protected function bindParam()
	{
		/** @var Controller $controller */
		if (is_array($this->handler)) {
			$controller = $this->handler[0];
		} else {
			$controller = $this->handler;
		}
		$request = \BeReborn::getApp('request');
		$controller->setRequest($request);
		$controller->setHeaders($request->headers);
		$controller->setInput($request->params);
	}

}
