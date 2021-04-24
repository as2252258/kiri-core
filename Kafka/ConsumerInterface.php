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
     * @return mixed
     */
    public function onHandler(Struct $struct): void;


    public function setParams(...$params): void;


}
