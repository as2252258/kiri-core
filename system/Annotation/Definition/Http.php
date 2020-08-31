<?php


namespace Snowflake\Annotation;


use Closure;
use ReflectionClass;
use ReflectionException;
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


}
