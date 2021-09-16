<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Http\IInterface\MiddlewareInterface;
use Http\Route\MiddlewareManager;

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
			$sn = di($value);
			if (!($sn instanceof MiddlewareInterface)) {
				continue;
			}
			$array[] = [$sn, 'onHandler'];
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
