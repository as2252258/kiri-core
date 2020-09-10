<?php


namespace Queue;


use Exception;
use Queue\Abstracts\AbstractsQueue;
use Queue\Abstracts\Relyon;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Running
 * @package Queue
 */
class Running extends \Queue\Abstracts\Queue
{

	const QUEUE_RUNNING = 'queue:runner:lists';


	/**
	 * @param Consumer $consumer
	 * @param int $score
	 * @return false|int
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function add(Consumer $consumer, $score = 0)
	{
		return $this->push(self::QUEUE_RUNNING, $consumer, $score);
	}


	/**
	 * @param $consumer
	 * @return false|int
	 * @throws ComponentException
	 */
	public function del(Consumer $consumer)
	{
		return $this->pop(self::QUEUE_RUNNING, $consumer);
	}


}
