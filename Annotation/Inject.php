<?php


namespace Annotation;


use Exception;
use ReflectionException;
use ReflectionProperty;
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
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function execute(mixed $class, mixed $method = null): bool
	{
		$injectValue = $this->parseInjectValue();
		if (!($method instanceof ReflectionProperty)) {
			$method = Snowflake::getDi()->getClassProperty($class, $method);
			if (!$method) {
				return false;
			}
		}

		/** @var ReflectionProperty $class */
		if ($method->isPrivate() || $method->isProtected()) {
			$method = 'set' . ucfirst($class->getName());
			if (!method_exists($class, $method)) {
				return false;
			}
			$class->$method($injectValue);
		} else {
			$class->{$method->getName()} = $injectValue;
		}
		return true;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	private function parseInjectValue(): mixed
	{
		if (class_exists($this->className)) {
			$injectValue = Snowflake::getDi()->get($this->className, $this->args);
		} else if (Snowflake::app()->has($this->className)) {
			$injectValue = Snowflake::app()->get($this->className);
		} else {
			$injectValue = $this->className;
		}
		return $injectValue;
	}

}
