<?php


namespace Annotation;


use ReflectionException;
use ReflectionProperty;
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
			$injectValue = Snowflake::createObject($this->className, $this->args);
		} else if (Snowflake::app()->has($this->className)) {
			$injectValue = Snowflake::app()->get($this->className);
		} else {
			$injectValue = $this->className;
		}
		if (!empty($this->args) && is_object($injectValue)) {
			Snowflake::configure($injectValue, $this->args);
		}
		/** @var ReflectionProperty $property */
		if ($property->isPrivate() || $property->isProtected()) {
			if (!method_exists($handler[0], 'set' . ucfirst($property->getName()))) {
				return false;
			}
			$object->{'set' . ucfirst($property->getName())}($injectValue);
		} else {
			$object->$property = $injectValue;
		}
		return $object;
	}
}
