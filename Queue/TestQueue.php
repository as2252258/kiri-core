<?php


namespace Queue;


use Snowflake\Snowflake;

/**
 * Class TestQueue
 * @package Queue
 */
class TestQueue implements Consumer
{

	public $params;


	/**
	 * TestQueue constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		$this->params = $params;
		$this->onWaiting();
	}

	/**
	 *
	 */
	public function onWaiting()
	{



	}

	public function onRunning()
	{
		// TODO: Implement onRunning() method.
	}

	public function onComplete()
	{
		// TODO: Implement onComplete() method.
	}
}
