<?php


namespace Snowflake\Annotation\Definition;


use Closure;
use ReflectionClass;
use ReflectionException;
use Snowflake\Annotation\Annotation;
use Snowflake\Snowflake;

/**
 * Class Http
 * @package Snowflake\Annotation
 */
class Http extends Annotation
{

	const HTTP_EVENT = 'http:event:';

	/**
	 * @var string
	 * @Interceptor(LoginInterceptor)
	 */
	private $Interceptor = 'required|not empty';


	/**
	 * @var string
	 */
	private $Limits = 'required|not empty';

	protected $_annotations = [];


	/**
	 * @param $events
	 * @return bool
	 */
	public function isLegitimate($events)
	{
		return isset($events[2]) && !empty($events[2]);
	}


	/**
	 * @param $name
	 * @param $events
	 * @return false|string
	 */
	public function getName($name, $events)
	{
		return self::HTTP_EVENT . $name . ':' . $events[2];
	}


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $events
	 * @return array|void
	 */
	public function createHandler($controller, $methodName, $events)
	{
		return [$controller, $methodName];
	}

}
