<?php
declare(strict_types=1);

namespace Kafka;


/**
 * Interface ConsumerInterface
 * @package App\Kafka
 */
interface ConsumerInterface
{


	/**
	 * @param Struct $struct
	 */
	public function setParams(Struct $struct): void;



	/**
	 * @return mixed
	 */
    public function process(): void;


}
