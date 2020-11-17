<?php
declare(strict_types=1);


namespace HttpServer\Route\Annotation;


use Snowflake\Annotation\Annotation;

/**
 * Class Tcp
 * @package HttpServer\Route\Annotation
 */
class Tcp extends Annotation
{


	const CONNECT = 'Connect';
	const PACKET = 'Packet';
	const RECEIVE = 'Receive';
	const CLOSE = 'Close';

	private string $Message = 'required|not empty';

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
	public function getName($events, $comment = [])
	{
		$prefix = 'TCP:ANNOTATION:' . ltrim($events, ':');
		if (isset($comment[2]) && !empty($comment[2])) {
			return $prefix . ':' . $comment[2];
		}
		return $prefix;
	}


}
