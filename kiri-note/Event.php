<?php


namespace Note;


use Exception;
use Kiri\Events\EventProvider;
use Kiri\Kiri;


/**
 * Class Event
 * @package Note
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
        $pro = Kiri::getDi()->get(EventProvider::class);
        if (is_string($class)) {
            $class = Kiri::getDi()->get($class);
        }
        $pro->on($this->name, [$class, $method]);
        return true;
    }

}
