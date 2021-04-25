<?php


namespace Annotation;


use Exception;
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
	 * @param array $handler
	 * @return mixed
	 * @throws Exception
	 */
	public function execute(array $handler): mixed
	{
		$injectValue = $this->parseInjectValue();
		if (!($handler[1] instanceof ReflectionProperty)) {
			$handler[1] = new ReflectionProperty($handler[0], $handler[1]);
		}

		/** @var ReflectionProperty $handler [1] */
		if ($handler[1]->isPrivate() || $handler[1]->isProtected()) {
			$method = 'set' . ucfirst($handler[1]->getName());
			if (!method_exists($handler[0], $method)) {
				return false;
			}
			$handler[0]->$method($injectValue);
		} else {
			$handler[0]->{$handler[1]->getName()} = $injectValue;
		}
		return $handler[0];
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	private function parseInjectValue(): mixed
	{
		if (class_exists($this->className)) {
			$injectValue = Snowflake::createObject($this->className, $this->args);
		} else if (Snowflake::app()->has($this->className)) {
			$injectValue = Snowflake::app()->get($this->className);
		} else {
			$injectValue = $this->className;
		}
//		if (!empty($this->args) && is_object($injectValue)) {
//			Snowflake::configure($injectValue, $this->args);
//		}
		return $injectValue;
	}

}
