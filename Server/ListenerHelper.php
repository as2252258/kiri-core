<?php

namespace Server;


use Closure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


/**
 * Trait ListenerHelper
 * @package Server
 */
trait ListenerHelper
{


	/**
	 * @param string $name
	 * @param array $events
	 * @param array|Closure $default
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
    protected static function callback(string $name, array $events, array|Closure $default): mixed
    {
        if (!is_array($events) || !isset($events[$name])) {
            return $default;
        }

        $callback = $events[$name];
        if ($callback instanceof Closure) {
            return $callback;
        }
        $object = Snowflake::getDi()->getReflect($callback[0]);
        if ($object->getMethod($callback[1])->isStatic()) {
            return $callback;
        }
        return [$object->newInstance(), $callback[1]];
    }


}
