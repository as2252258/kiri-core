<?php


namespace HttpServer\Route\Annotation;

use HttpServer\Route\Node;
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


	private $Method = 'post';


	private $Middleware = '';


//	private $Route = '';


	protected $_annotations = [];


	/**
	 * @param Node $node
	 * @param ReflectionClass $reflect
	 * @param $method
	 * @param $annotations
	 * @throws ReflectionException
	 */
	public function read($node, $reflect, $method, $annotations)
	{
		$method = $reflect->getMethod($method);

		$_annotations = $this->getDocCommentAnnotation($annotations, $method->getDocComment());

		foreach ($_annotations as $keyName => $annotation) {
			if (!in_array($keyName, $annotations)) {
				continue;
			}
			$this->bind($keyName, $node, $annotation);
		}
	}


	/**
	 * @param $keyName
	 * @param $node
	 * @param $annotation
	 */
	private function bind($keyName, $node, $annotation)
	{
		switch ($keyName) {
			case 'Method':
				$this->bindMethod($node, $annotation);
				break;
			case 'Middleware':
				$this->bindMiddleware($node, $this->pop($this->getName(...$annotation)));
				break;
			case'Interceptor':
				$this->bindInterceptors($node, $this->pop($this->getName(...$annotation)));
				break;
		}
	}

	/**
	 * @param $node
	 * @param $annotation
	 */
	private function bindMethod($node, $annotation)
	{
		if (!isset($annotation[1][2])) {
			return;
		}
		$explode = explode(',', $annotation[1][2]);
		if (in_array('any', $explode)) {
			$explode = ['*'];
		}
		$node->method = $explode;
	}


	/**
	 * @param Node $node
	 * @param $annotation
	 * @throws
	 */
	private function bindMiddleware($node, $annotation)
	{
		if (!isset($annotation[1][2])) {
			return;
		}

//		$explode = explode(',', $annotation[1][2]);
//		foreach ($explode as $middleware) {
//			$middleware = 'App\Http\Interceptor\\' . $middleware;
//			if (!class_exists($middleware)) {
//				continue;
//			}
//		}
		$node->addMiddleware($annotation);

	}


	/**
	 * @param Node $node
	 * @param $annotation
	 * @throws
	 */
	private function bindInterceptors($node, $annotation)
	{
		if (!isset($annotation[1][2])) {
			return;
		}

//		$explode = explode(',', $annotation[1][2]);
//		foreach ($explode as $middleware) {
//		}
		$node->addInterceptor($annotation);

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
