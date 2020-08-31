<?php

namespace HttpServer\Route\Annotation;


use Snowflake\Annotation\Annotation;

/**
 * Class Websocket
 * @package Snowflake\Annotation
 */
class Websocket extends Annotation
{

	const MESSAGE = 'Message:';
	const HANDSHAKE = 'Handshake:';
	const CLOSE = 'Close:';

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
	 * @return bool|void
	 */
	public function isLegitimate($events)
	{
		return true;
	}


	/**
	 * @param $events
	 * @param $comment
	 * @return false|string
	 */
	public function getName($events, $comment)
	{
		$prefix = 'WEBSOCKET:ANNOTATION:' . $events;
		if (isset($comment[2])) {
			return rtrim($prefix, ':') . ':' . $comment[2];
		}
		return $prefix;
	}

}
