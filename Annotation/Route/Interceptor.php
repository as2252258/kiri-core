<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor implements IAnnotation
{

	use Node;

	/**
	 * Interceptor constructor.
	 * @param string|array $interceptor
	 * @throws
	 */
	#[Pure] public function __construct(public string|array $interceptor)
	{
		if (is_string($this->interceptor)) {
			$this->interceptor = [$this->interceptor];
		}
	}


	/**
	 * @param array $handler
	 * @return Interceptor
	 */
	public function execute(array $handler): static
	{
		return $this;
	}

}
