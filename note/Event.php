<?php


namespace Annotation;


use Exception;
use Kiri\Events\EventProvider;


/**
 * Class Event
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Event extends Attribute
{


    /**
     * Event constructor.
     * @param string $name
     * @param array $params
     */
    public function __construct(public string $name, public array $params = [])
    {
    }


    /**
     * @param mixed $class
     * @param mixed|null $method
     * @return bool
     * @throws Exception
     */
    public function execute(mixed $class, mixed $method = null): bool
    {
        $pro = di(EventProvider::class);
        if (is_string($class)) {
            $class = di($class);
        }
        $pro->on($this->name, [$class, $method]);
        return true;
    }

}
