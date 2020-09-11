<?php


namespace Queue;


use Queue\Abstracts\Relyon;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;


/**
 * Class Complete
 * @package Queue
 */
class Complete extends \Queue\Abstracts\Queue
{

	const QUEUE_COMPLETE = 'queue:complete:lists';


	/**
	 * @param Consumer $consumer
	 * @param int $score
	 * @return false|int
	 * @throws ComponentException
	 */
	public function add(Consumer $consumer, $score = 0)
	{
		return $this->push(self::QUEUE_COMPLETE, $consumer, $score);
	}


	/**
	 * @param $consumer
	 * @return false|int
	 * @throws ComponentException
	 */
	public function del(string $consumer)
	{
		return $this->pop(self::QUEUE_COMPLETE, $consumer);
	}


}
