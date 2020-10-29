<?php
declare(strict_types=1);

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
	 * @param string $method
	 * @param array $annotations
	 * @return array
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function instance(ReflectionClass $reflect, $method = '', $annotations = [])
	{
		$classMethods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);
		if (!$reflect->isInstantiable()) {
			throw new Exception('Class ' . $reflect->getName() . ' cannot be instantiated.');
		}

		$array = [];
		$object = $reflect->newInstance();
		if (!empty($method)) {
			$array = $this->resolveDocComment($reflect->getMethod($method), $object, $annotations, $array);
		} else {
			foreach ($classMethods as $classMethod) {
				$array = $this->resolveDocComment($classMethod, $object, $annotations, $array);
			}
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
	protected function resolveDocComment(ReflectionMethod $function, $object, $annotations, $array)
	{
		$comment = $function->getDocComment();
		$array = $this->getDocCommentAnnotation($annotations, $comment);
		foreach ($array as $name => $annotation) {
			foreach ($annotation as $index => $events) {
				if (!isset($events[1])) {
					continue;
				}
				if (!($_key = $this->getName($name, $events))) {
					continue;
				}
				if (isset($item[2])) {
					$handler = Snowflake::createObject($events[2]);
				} else {
					$handler = [$object, $events[1]];
				}
				if (!isset($array[$annotation])) {
					$array[$annotation] = [];
				}
				$array[$name][] = [$_key, $handler];
			}

		}
		return $array;
	}


	/**
	 * @param $object
	 * @param $events
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	protected function getOrCreate($object, $events)
	{
		if (isset($item[2])) {
			$handler = Snowflake::createObject($events[2]);
		} else {
			$handler = [$object, $events[1]];
		}
	}


	/**
	 * @param $annotations
	 * @param $comment
	 * @return array
	 */
	protected function getDocCommentAnnotation($annotations, $comment)
	{
		$array = [];
		foreach ($annotations as $annotation) {
			preg_match('/@(' . $annotation . ')\((.*?)\)/', $comment, $events);
			if (!isset($events[1])) {
				continue;
			}
			if (!isset($array[$annotation])) {
				$array[$annotation] = [];
			}
			$array[$annotation] = [$annotation, $events];
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
