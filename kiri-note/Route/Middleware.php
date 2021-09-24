<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Http\Handler\Abstracts\MiddlewareManager;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)] class Middleware extends Attribute
{


	/**
	 * Interceptor constructor.
	 * @param string|array $middleware
	 * @throws
	 */
	public function __construct(public string|array $middleware)
	{
		if (is_string($this->middleware)) {
			$this->middleware = [$this->middleware];
		}
		$array = [];
		foreach ($this->middleware as $value) {
			if (!in_array(MiddlewareInterface::class, class_implements($value))) {
				throw new \Exception('The middleware');
			}
			$array[] = $value;
		}
		$this->middleware = $array;
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return $this
	 */
	public function execute(mixed $class, mixed $method = null): mixed
	{
		MiddlewareManager::add($class, $method, $this->middleware);
		return parent::execute($class, $method);
	}


}
