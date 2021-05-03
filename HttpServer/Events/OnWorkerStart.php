<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Annotation;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Runtime;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\System;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{


    /**
     * @param Server $server
     * @param int $worker_id
     *
     * @return mixed
     * @throws Exception
     */
    public function onHandler(Server $server, int $worker_id): void
    {
        putenv('state=start');
        putenv('worker=' . $worker_id);

        $annotation = Snowflake::app()->getAnnotation();
        $annotation->setLoader(unserialize(file_get_contents(storage(Runtime::CACHE_NAME))));
        if ($worker_id >= $server->setting['worker_num']) {
            $annotation->runtime(APP_PATH, [CONTROLLER_PATH, TASK_PATH, LISTENER_PATH]);
            $this->onTask($server, $annotation);
        } else {
            $annotation->runtime(CONTROLLER_PATH);
            $annotation->runtime(directory('app'), [CONTROLLER_PATH]);
            $this->onWorker($server, $annotation);
        }
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @return bool
     */
    private function isWorker(Server $server, int $worker_id): bool
    {
        return $worker_id < $server->setting['worker_num'];
    }


    /**
     * @param Server $server
     * @param Annotation $annotation
     * @throws ConfigException
     * @throws Exception
     */
    public function onTask(Server $server, Annotation $annotation)
    {
        putenv('environmental=' . Snowflake::TASK);

        $annotation->runtime(directory('app'), [
            CONTROLLER_PATH,
            TASK_PATH,
        ]);
        name($server->worker_pid, 'Task#' . $server->worker_id);

        Snowflake::setTaskId($server->worker_pid);

        fire(Event::SERVER_TASK_START);
    }


    /**
     * @param Server $server
     * @param Annotation $annotation
     * @throws Exception
     */
    public function onWorker(Server $server, Annotation $annotation)
    {
        name($server->worker_pid, 'Worker#' . $server->worker_id);

        $time = microtime(true);
        $annotation->runtime(CONTROLLER_PATH);
        $this->debug('use time.' . (microtime(true) - $time));
        $annotation->runtime(directory('app'), CONTROLLER_PATH);

        Snowflake::setWorkerId($server->worker_pid);
        putenv('environmental=' . Snowflake::WORKER);

        fire(Event::SERVER_WORKER_START, [getenv('worker')]);
    }

}
