<?php


namespace Snowflake\Abstracts;


use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class BaseAnnotation
 * @package Snowflake\Snowflake\Annotation\Base
 */
abstract class BaseAnnotation extends Component
{


	/**
	 * @param ReflectionClass $reflect
	 * @return array
	 */
	protected function getPrivates(ReflectionClass $reflect)
	{
		$arrays = [];
		$properties = $reflect->getProperties(ReflectionMethod::IS_PRIVATE);
		foreach ($properties as $property) {
			$arrays[] = $property->getName();
		}
		return $arrays;
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param array $rules
	 * @return array
	 * @throws Exception
	 */
	public function instance($reflect, $rules = [])
	{
		$annotations = $this->getPrivates($reflect);

		$classMethods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);
		if (!$reflect->isInstantiable()) {
			throw new Exception('Class ' . $reflect->getName() . ' cannot be instantiated.');
		}

		$object = $reflect->newInstance();

		$array = [];
		foreach ($classMethods as $classMethod) {
			$array = $this->resolveDocComment($classMethod, $object, $annotations, $array);
		}
		return $array;
	}


	/**
	 * @param ReflectionMethod $function
	 * @param $object
	 * @param $annotations
	 * @param $array
	 * @return array
	 * @throws
	 */
	protected function resolveDocComment($function, $object, $annotations, $array)
	{
		$comment = $function->getDocComment();
		foreach ($annotations as $annotation) {
			preg_match('/@(' . $annotation . ')\((.*?)\)/', $comment, $events);
			if (!isset($events[1])) {
				continue;
			}
			if (!($_key = $this->getName($function, $events))) {
				continue;
			}
			if (isset($events[2])) {
				$handler = Snowflake::createObject($events[2]);
			} else {
				$handler = [$object, $events[1]];
			}
			if (!isset($array[$annotation])) {
				$array[$annotation] = [];
			}
			$array[$annotation][] = [$_key, $handler];
		}
		return $array;
	}


	/**
	 * @param $rule
	 * @param $content
	 * @param $rules
	 * @return bool
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function check($rule, $content, $rules)
	{
		if (empty($rule)) {
			return true;
		}
		$explode = explode('|', $rule);
		foreach ($explode as $value) {
			$reflect = array_merge($rules[$value], [
				'value' => $content
			]);
			$validator = Snowflake::createObject($reflect);
			if (!$validator->check()) {
				throw new Exception($validator->getMessage());
			}
		}
		return false;
	}


	abstract public function runWith($path);

}
