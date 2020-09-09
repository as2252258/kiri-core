<?php


namespace HttpServer\Route\Annotation;

use HttpServer\IInterface\After;
use HttpServer\IInterface\Interceptor;
use HttpServer\IInterface\Limits;
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


	private $After = '';


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
			case'Interceptor':
				$this->bindInterceptors($node, $annotation);
				break;
			case 'Middleware':
				$this->bindMiddleware($node, $annotation);
				break;
			case 'Limits':
				$this->bindLimits($node, $annotation);
				break;
			case 'After':
				$this->bindAfter($node, $annotation);
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
		$explode = explode(',', $annotation[1][2]);
		foreach ($explode as $middleware) {
			$middleware = 'App\Http\Middleware\\' . $middleware;
			if (!class_exists($middleware)) {
				continue;
			}
			$node->addMiddleware($middleware);
		}
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
		$explode = explode(',', $annotation[1][2]);
		foreach ($explode as $middleware) {
			$middleware = 'App\Http\Interceptor\\' . $middleware;
			if (!class_exists($middleware)) {
				continue;
			}
			$middleware = Snowflake::createObject($middleware);
			if (!($middleware instanceof Interceptor)) {
				continue;
			}
			$node->addInterceptor([$middleware, 'Interceptor']);
		}
	}


	/**
	 * @param Node $node
	 * @param $annotation
	 * @throws
	 */
	private function bindAfter($node, $annotation)
	{
		if (!isset($annotation[1][2])) {
			return;
		}

		$explode = explode(',', $annotation[1][2]);
		foreach ($explode as $middleware) {
			$middleware = 'App\Http\After\\' . $middleware;
			if (!class_exists($middleware)) {
				continue;
			}
			$middleware = Snowflake::createObject($middleware);
			if (!($middleware instanceof After)) {
				continue;
			}
			$node->addAfter([$middleware, 'onHandler']);
		}
	}


	/**
	 * @param Node $node
	 * @param $annotation
	 * @throws
	 */
	private function bindLimits($node, $annotation)
	{
		if (!isset($annotation[1][2])) {
			return;
		}

		$explode = explode(',', $annotation[1][2]);
		foreach ($explode as $middleware) {
			$middleware = 'App\Http\Limits\\' . $middleware;
			if (!class_exists($middleware)) {
				continue;
			}
			$middleware = Snowflake::createObject($middleware);
			if (!($middleware instanceof Limits)) {
				continue;
			}
			$node->addLimits([$middleware, 'next']);
		}
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
