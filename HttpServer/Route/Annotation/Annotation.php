<?php


namespace HttpServer\Route\Annotation;

use ReflectionClass;
use ReflectionException;
use Snowflake\Abstracts\BaseAnnotation;
use Snowflake\Snowflake;

/**
 * Class Annotation
 */
class Annotation extends \Snowflake\Annotation\Annotation
{

	const HTTP_EVENT = 'http:event:';
	const CLOSE = 'Close';

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
	 * @param ReflectionClass $reflect
	 * @param $method
	 * @param $annotations
	 * @return mixed|null
	 * @throws ReflectionException
	 */
	public function read($reflect, $method, $annotations)
	{
		$method = $reflect->getMethod($method);

		$_annotations = $this->getDocCommentAnnotation($annotations, $method->getDocComment());

		$array = [];
		foreach ($_annotations as $keyName => $annotation) {
			if (!in_array($keyName, $annotations)) {
				continue;
			}
			$array[$keyName] = $this->pop($this->getName(...$annotation));
		}
		return $array;
	}


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $events
	 * @return array|void
	 * @throws
	 */
	public function createHandler($controller, $methodName, $events)
	{
		return Snowflake::createObject($events[2]);
	}




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
