<?php


namespace Annotation;


use Exception;
use Kiri\Core\Str;
use Kiri\Kiri;
use ReflectionException;
use ReflectionProperty;

/**
 * Class Inject
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)] class Inject extends Attribute
{


	/**
	 * Inject constructor.
	 * @param string $value
	 * @param array $construct
	 */
	public function __construct(string $value, array $construct = [])
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return bool
	 * @throws ReflectionException
	 * @throws Exception
	 */
    public static function execute(mixed $params, mixed $class, mixed $method = null): bool
	{
		if (!($method = static::getProperty($class, $method))) {
			return false;
		}
		/** @var ReflectionProperty $class */
		$injectValue = static::parseInjectValue($params);
		if ($method->isPrivate() || $method->isProtected()) {
			static::setter($class, $method, $injectValue);
		} else {
			$class->{$method->getName()} = $injectValue;
		}
		return true;
	}


	/**
	 * @param $class
	 * @param $method
	 * @param $injectValue
	 */
	private static function setter($class, $method, $injectValue)
	{
		$method = 'set' . ucfirst(Str::convertUnderline($method->getName()));
		if (!method_exists($class, $method)) {
			return;
		}
		$class->$method($injectValue);
	}


	/**
	 * @param $class
	 * @param $method
	 * @return ReflectionProperty|bool
	 * @throws ReflectionException
	 */
	private static function getProperty($class, $method): ReflectionProperty|bool
	{
		if ($method instanceof ReflectionProperty && !$method->isStatic()) {
			return $method;
		}
		if (is_object($class)) $class = $class::class;
		$method = Kiri::getDi()->getClassReflectionProperty($class, $method);
		if (!$method || $method->isStatic()) {
			return false;
		}
		return $method;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	private static function parseInjectValue($params): mixed
	{
		if (!Kiri::app()->has($params->value)) {
			if (!empty($params->construct)) {
				return Kiri::getDi()->newObject($params->value, $params->construct);
			}
			return Kiri::getDi()->get($params->value);
		} else {
			return Kiri::app()->get($params->value);
		}
	}

}
