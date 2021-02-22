<?php


namespace Annotation;


use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Inject
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)] class Inject implements IAnnotation
{


	/**
	 * Inject constructor.
	 * @param string $className
	 * @param array $args
	 */
	public function __construct(private string $className, private array $args = [])
	{
	}


	/**
	 * @param array $handler
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException|ComponentException
	 */
	public function execute(array $handler): mixed
	{
		if (Snowflake::app()->has($this->className)) {
			return Snowflake::app()->get($this->className);
		}
		return Snowflake::createObject($this->className, $this->args);
	}
}
