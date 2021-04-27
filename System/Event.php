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

    private array $_events = [];

    const EVENT_ERROR = 'WORKER:ERROR';
    const EVENT_STOP = 'WORKER:STOP';
    const EVENT_EXIT = 'WORKER:EXIT';

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


    /**
     * @param $name
     * @param $callback
     * @param array $parameter
     * @param bool $isAppend
     * @throws Exception
     */
    public function on($name, $callback, $parameter = [], $isAppend = false)
    {
        if (!isset($this->_events[$name])) {
            $this->_events[$name] = [];
        }
        if ($callback instanceof \Closure) {
            $callback = \Closure::bind($callback, Snowflake::app());
        } else if (is_array($callback) && is_string($callback[0])) {
            if (!class_exists($callback[0])) {
                throw new Exception('Undefined callback class.');
            }
            $callback[0] = Snowflake::createObject($callback[0]);
        }
        if ($this->exists($name, $callback)) {
            return;
        }
        if (!empty($this->_events[$name]) && $isAppend === true) {
            array_unshift($this->_events[$name], [$callback, $parameter]);
        } else {
            $this->_events[$name][] = [$callback, $parameter];
        }
    }


    /**
     * @param $name
     * @param $callback
     */
    public function of($name, $callback): void
    {
        if (!isset($this->_events[$name])) {
            return;
        }
        foreach ($this->_events[$name] as $index => $event) {
            [$handler, $parameter] = $event;
            if ($handler !== $callback) {
                continue;
            }
            unset($this->_events[$name][$index]);
        }
    }


    /**
     * @param $name
     * @return bool
     */
    public function offName($name): bool
    {
        if (!$this->exists($name)) {
            return true;
        }
        unset($this->_events[$name]);
        return $this->exists($name);
    }


    /**
     * @param $name
     * @param null $callback
     * @return bool
     */
    public function exists($name, $callback = null): bool
    {
        if (!isset($this->_events[$name])) {
            return false;
        }
        if ($callback === null) {
            return true;
        }
        foreach ($this->_events[$name] as $event) {
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
    public function get($name, $handler): mixed
    {
        if (!$this->exists($name, $handler)) {
            return null;
        }

        if (empty($handler)) {
            return $this->_events[$name];
        }
        foreach ($this->_events[$name] as $event) {
            [$callback, $parameter] = $event;
            if ($callback === $handler) {
                return [$event];
            }
        }
        return null;
    }


    public function clean()
    {
        $this->_events = [];
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
        return $this->trigger($name, $params, $scope);
    }


	/**
	 * @param $name
	 * @param null $parameter
	 * @param null $handler
	 * @param false $is_remove
	 * @return bool
	 * @throws Exception
	 */
    public function trigger($name, $parameter = null, $handler = null, $is_remove = false): bool
    {
        $events = $this->get($name, $handler);
        if (empty($events)) {
            return true;
        }
//        foreach ($events as $index => $event) {
//            $this->execute($event, $parameter);
//        }
	    call_user_func_array($events, ...$parameter);
	    if ($is_remove) {
            $this->offName($name);
        }
        return true;
    }


    /**
     * @param $event
     * @param $parameter
     * @return bool
     * @throws Exception
     */
    private function execute($event, $parameter): bool
    {
        try {
            $meta = $this->mergeParams($event[1], $parameter);
            if (call_user_func($event[0], ...$meta) === false) {
                return false;
            }
            return true;
        } catch (\Throwable $throwable) {
            return $this->addError($throwable,'throwable');
        }
    }


    /**
     * @param $defaultParameter
     * @param $parameter
     * @return array
     */
    private function mergeParams($defaultParameter, $parameter = []): array
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
