<?php

namespace Snowflake\Annotation\Definition;


use ReflectionClass;
use Snowflake\Annotation\Annotation;

/**
 * Class Websocket
 * @package Snowflake\Annotation
 */
class Websocket extends Annotation
{

	const WEBSOCKET_ANNOTATION = 'WEBSOCKET:ANNOTATION:';

	private $Message = 'required|not empty';


	private $Handshake;


	private $Close;


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $events
	 * @return array
	 */
	public function createHandler($controller, $methodName, $events)
	{
		return [$controller, $methodName];
	}


	/**
	 * @param $events
	 * @param $comment
	 * @return false|string
	 */
	public function getName($events, $comment)
	{
		$prefix = self::WEBSOCKET_ANNOTATION . $events;
		if (isset($comment[2])) {
			return $prefix . ':' . $comment[2];
		}
		return $prefix;
	}

}
