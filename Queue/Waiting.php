<?php


namespace Queue;


use Exception;
use Queue\Abstracts\Relyon;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Waiting
 * @package Queue
 */
class Waiting extends \Queue\Abstracts\Queue
{

	const QUEUE_WAITING = 'queue:waiting:lists';


	/**
	 * @param Consumer $consumer
	 * @param int $score
	 * @return false|int
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function add(Consumer $consumer, $score = 0)
	{
		return $this->push(self::QUEUE_WAITING, $consumer, $score);
	}


	/**
	 * @param $consumer
	 * @return false|int
	 * @throws ComponentException
	 */
	public function del(Consumer $consumer)
	{
		return $this->pop(self::QUEUE_WAITING, $consumer);
	}

}
