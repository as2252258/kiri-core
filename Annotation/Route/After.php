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
#[\Attribute(\Attribute::TARGET_METHOD)] class After implements IAnnotation
{

	use Node;

	/**
	 * Interceptor constructor.
	 * @param \HttpServer\IInterface\After|\HttpServer\IInterface\After[] $after
	 * @throws
	 */
	#[Pure] public function __construct(public string|array $after)
	{
		if (is_string($this->after)) {
			$this->after = [$this->after];
		}
	}


	/**
	 * @param array $handler
	 * @return After
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
