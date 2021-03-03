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
#[\Attribute(\Attribute::TARGET_PROPERTY)] class Inject extends Attribute
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
		[$object, $property] = $handler;
		if (class_exists($this->className)) {
			return $object->$property = Snowflake::createObject($this->className, $this->args);
		}

		$application = Snowflake::app();
		if (!$application->has($this->className)) {
			return $object;
		}

		$object->$property = $application->get($this->className);
		if (!empty($this->args) && is_object($object->$property)) {
			Snowflake::configure($object->$property, $this->args);
		}
		return $object;
	}
}
