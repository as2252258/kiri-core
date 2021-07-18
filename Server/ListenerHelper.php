<?php

namespace Server;


use Snowflake\Snowflake;

trait ListenerHelper
{


    /**
     * @param $server
     * @param $newServer
     */
    public static function onConnectAndClose($settings, $newServer)
    {
        $reflect = Snowflake::getDi()->getReflect(static::class)?->newInstance();

        $newServer->on('connect', static::callback(Constant::CONNECT, $settings['events'], [$reflect, 'onConnect']));
        $newServer->on('close', static::callback(Constant::CLOSE, $settings['events'], [$reflect, 'onClose']));
    }


    /**
     * @param string $name
     * @param array $events
     * @param array|\Closure $default
     * @return array|\Closure|mixed
     * @throws \ReflectionException
     * @throws \Snowflake\Exception\NotFindClassException
     */
    protected static function callback(string $name, array $events, array|\Closure $default)
    {
        if (!is_array($events) || !isset($events[$name])) {
            return $default;
        }

        $callback = $events[$name];
        if ($callback instanceof \Closure) {
            return $callback;
        }
        $object = Snowflake::getDi()->getReflect($callback[0]);
        if ($object->getMethod($callback[1])->isStatic()) {
            return $callback;
        }
        return [$object->newInstance(), $callback[1]];
    }


}
