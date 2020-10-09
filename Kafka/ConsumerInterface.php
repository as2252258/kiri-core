<?php


namespace Kafka;


/**
 * Interface ConsumerInterface
 * @package App\Kafka
 */
interface ConsumerInterface
{


	/**
	 * @param Struct $struct
	 * @return mixed
	 */
	public function onHandler(Struct $struct);


}
