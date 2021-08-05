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
	 * @return mixed
	 */
    public function process(): void;


}
