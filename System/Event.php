<?php
declare(strict_types=1);


namespace Snowflake;


use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Core\ArrayAccess;

/**
 * Class Event
 * @package Snowflake
 */
class Event extends BaseObject
{

    public bool $isVide = true;

    private static array $_events = [];


    const PIPE_MESSAGE = 'SERVER:PIPE:MESSAGE';
    const TASK_FINISH = 'SERVER:TASK::FINISH';

    const EVENT_AFTER_REQUEST = 'SERVER:REQUEST:AFTER:START';
    const EVENT_BEFORE_REQUEST = 'SERVER:REQUEST:BEFORE:START';
    const RECEIVE_CONNECTION = 'SERVER:RECEIVE:CONNECTION';


    const SYSTEM_RESOURCE_RELEASES = 'SYSTEM::RESOURCE::RELEASES';
    const SYSTEM_RESOURCE_CLEAN = 'SYSTEM::RESOURCE::CLEAN';


    const PROCESS_WORKER_STOP = 'SERVER:PROCESS:WORKER:STOP';

    const SERVER_AFTER_RELOAD = 'SERVER:AFTER:RELOAD';
    const SERVER_BEFORE_RELOAD = 'SERVER:BEFORE:RELOAD';
    const SERVER_CONNECT = 'SERVER:CONNECT';
    const SERVER_PACKAGE = 'SERVER:PACKAGE';
    const SERVER_RECEIVE = 'SERVER:RECEIVE';

    const SERVER_EVENT_START = 'SERVER:EVENT:START';
    const SERVER_MANAGER_START = 'SERVER:EVENT:MANAGER:START';
    const SERVER_MANAGER_STOP = 'SERVER:EVENT:MANAGER:START';
    const SERVER_WORKER_STOP = 'SERVER:EVENT:WORKER:STOP';
    const SERVER_WORKER_START = 'SERVER:EVENT:WORKER:START';
    const SERVER_AFTER_WORKER_START = 'SERVER:EVENT:AFTER:WORKER:START';
    const SERVER_BEFORE_START = 'SERVER:EVENT:BEFORE:START';
    const BEFORE_COMMAND_EXECUTE = 'COMMAND:EVENT:BEFORE:EXECUTE';
    const AFTER_COMMAND_EXECUTE = 'COMMAND:EVENT:AFTER:EXECUTE';
    const SERVER_TASK_START = 'SERVER:EVENT:TASK:START';
    const SERVER_WORKER_EXIT = 'SERVER:EVENT:WORKER:EXIT';
    const SERVER_WORKER_ERROR = 'SERVER:EVENT:WORKER:ERROR';
    const SERVER_SHUTDOWN = 'SERVER:EVENT:SHUTDOWN';

    const SERVER_HANDSHAKE = 'on handshake';
    const SERVER_MESSAGE = 'on message';
    const SERVER_CLIENT_CLOSE = 'SERVER:CLIENT:CLOSE';

    const SERVER_ON_START = 'Start';
    const SERVER_ON_SHUTDOWN = 'Shutdown';
    const SERVER_ON_WORKER_START = 'WorkerStart';
    const SERVER_ON_WORKER_STOP = 'WorkerStop';
    const SERVER_ON_WORKER_EXIT = 'WorkerExit';
    const SERVER_ON_CONNECT = 'Connect';
    const SERVER_ON_RECEIVE = 'Receive';
    const SERVER_ON_PACKET = 'Packet';
    const SERVER_ON_REQUEST = 'request';
    const SERVER_ON_CLOSE = 'Close';
    const SERVER_ON_TASK = 'Task';
    const SERVER_ON_FINISH = 'Finish';
    const SERVER_ON_PIPE_MESSAGE = 'PipeMessage';
    const SERVER_ON_WORKER_ERROR = 'WorkerError';
    const SERVER_ON_MANAGER_START = 'ManagerStart';
    const SERVER_ON_MANAGER_STOP = 'ManagerStop';
    const SERVER_ON_BEFORE_RELOAD = 'BeforeReload';
    const SERVER_ON_AFTER_RELOAD = 'AfterReload';


    /**
     * @param $name
     * @param $callback
     * @param array $parameter
     * @param bool $isAppend
     * @throws Exception
     */
    public static function on($name, $callback, $parameter = [], $isAppend = false)
    {
        if (!isset(static::$_events[$name])) {
            static::$_events[$name] = [];
        }
        if ($callback instanceof \Closure) {
            $callback = \Closure::bind($callback, Snowflake::app());
        } else if (is_array($callback) && is_string($callback[0])) {
            if (!class_exists($callback[0])) {
                throw new Exception('Undefined callback class.');
            }
            $callback[0] = Snowflake::createObject($callback[0]);
        }
        if (static::exists($name, $callback)) {
            return;
        }
        if (!empty(static::$_events[$name]) && $isAppend === true) {
            array_unshift(static::$_events[$name], [$callback, $parameter]);
        } else {
            static::$_events[$name][] = [$callback, $parameter];
        }
    }


    /**
     * @param $name
     * @param $callback
     */
    public static function of($name, $callback): void
    {
        if (!isset(static::$_events[$name])) {
            return;
        }
        foreach (static::$_events[$name] as $index => $event) {
            [$handler, $parameter] = $event;
            if ($handler !== $callback) {
                continue;
            }
            unset(static::$_events[$name][$index]);
        }
    }


    /**
     * @param $name
     * @return bool
     */
    public static function offName($name): bool
    {
        if (!static::exists($name)) {
            return true;
        }
        unset(static::$_events[$name]);
        return static::exists($name);
    }


    /**
     * @param $name
     * @param null $callback
     * @return bool
     */
    public static function exists($name, $callback = null): bool
    {
        if (!isset(static::$_events[$name])) {
            return false;
        }
        if ($callback === null) {
            return true;
        }
        foreach (static::$_events[$name] as $event) {
            [$handler, $parameter] = $event;
            if ($handler === $callback) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param $name
     * @param $handler
     * @return mixed
     */
    public static function get($name, $handler): mixed
    {
        if (!static::exists($name, $handler)) {
            return null;
        }

        if (empty($handler)) {
            return static::$_events[$name];
        }
        foreach (static::$_events[$name] as $event) {
            [$callback, $parameter] = $event;
            if ($callback === $handler) {
                return [$event];
            }
        }
        return null;
    }


    public static function clean()
    {
        static::$_events = [];
    }


	/**
	 * @param $name
	 * @param array $params
	 * @param null $scope
	 * @return bool
	 * @throws Exception
	 */
    public function dispatch($name, $params = [], $scope = null): bool
    {
        return static::trigger($name, $params, $scope);
    }


	/**
	 * @param $name
	 * @param null $parameter
	 * @param null $handler
	 * @param false $is_remove
	 * @return bool
	 * @throws Exception
	 */
    public static function trigger($name, $parameter = null, $handler = null, bool $is_remove = false): bool
    {
        $events = static::get($name, $handler);
        if (empty($events)) {
            return true;
        }
        foreach ($events as $event) {
            static::execute($event, $parameter);
        }
	    if ($is_remove) {
            static::offName($name);
        }
        return true;
    }


    /**
     * @param $event
     * @param $parameter
     * @return bool
     * @throws Exception
     */
    private static function execute($event, $parameter): bool
    {
        try {
            $meta = static::mergeParams($event[1], $parameter);
            if (call_user_func($event[0], ...$meta) === false) {
                return false;
            }
            return true;
        } catch (\Throwable $throwable) {
            return logger()->addError($throwable,'throwable');
        }
    }


    /**
     * @param $defaultParameter
     * @param array $parameter
     * @return array
     */
    private static function mergeParams($defaultParameter, array $parameter = []): array
    {
        if (empty($defaultParameter)) {
            $defaultParameter = $parameter;
        } else {
            if (!is_array($parameter)) {
                $parameter = [];
            }
            foreach ($parameter as $key => $value) {
                $defaultParameter[] = $value;
            }
        }
        if (!is_array($defaultParameter)) {
            $defaultParameter = [$defaultParameter];
        }
        return $defaultParameter;
    }


}
