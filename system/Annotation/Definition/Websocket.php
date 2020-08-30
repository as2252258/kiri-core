<?php

namespace Snowflake\Annotation;


use ReflectionClass;

/**
 * Class Websocket
 * @package Snowflake\Annotation
 */
class Websocket extends Annotation
{

	const MESSAGE = 'WEBSOCKET:MESSAGE:';
	const EVENT = 'WEBSOCKET:EVENT:';

	public $Message;


	public $Event;


	/**
	 * @param ReflectionClass $reflect
	 * @param array $methods
	 */
	public function resolve(ReflectionClass $reflect, array $methods)
	{
		$controller = $reflect->newInstance();

		foreach ($methods as $function) {
			$comment = $function->getDocComment();
			$methodName = $function->getName();

			preg_match('/@Event\((.*)?\)/', $comment, $events);
			if (!isset($events[1])) {
				continue;
			}

			if (!($_key = $this->getName($events, $comment))) {
				continue;
			}
			$this->push($_key, [$controller, $methodName]);
		}
	}

	/**
	 * @param $events
	 * @param $comment
	 * @return false|string
	 */
	private function getName($events, $comment)
	{
		$event = $events[1];
		if ($event !== 'message') {
			return self::EVENT . $event;
		}
		preg_match('/@Message\((.*)?\)/', $comment, $message);
		if (isset($message[1])) {
			return false;
		}
		return self::MESSAGE . $message[1];
	}

}
