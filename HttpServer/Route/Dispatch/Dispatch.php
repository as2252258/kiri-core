<?php
declare(strict_types=1);


namespace HttpServer\Route\Dispatch;


use Closure;
use HttpServer\Controller;
use HttpServer\Http\Context;

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
	 * @return static
	 */
	public static function create($handler): static
	{
		$class = new static();
		$class->handler = $handler;
		if ($handler instanceof Closure) {
			$class->bind();
		}
		return $class;
	}


	/**
	 * @return mixed
	 * 执行函数
	 * @throws \Exception
	 */
	public function dispatch(): mixed
	{
        $dispatchParam = Context::getContext('dispatch-param');
        if (empty($dispatchParam)) {
            $dispatchParam = [\request()];
        }
		return \aop($this->handler, $dispatchParam);
	}


	/**
	 *
	 */
	protected function bind()
	{
		$this->handler = Closure::bind($this->handler, new Controller());
	}

}
