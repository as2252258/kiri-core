<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Loader;
use Annotation\Target;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Process\ServerInotify;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;

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

        name($server->worker_pid, $worker_id >= $server->setting['worker_num'] ? 'task' : 'worker');

        $loader = Snowflake::app()->get(ServerInotify::class);

        if ($worker_id >= $server->setting['worker_num']) {
            $this->onTask($server, $worker_id, $loader);
        } else {
            $this->onWorker($server, $worker_id, $loader);
        }
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @throws Exception
     */
    public function onTask(Server $server, int $worker_id, Loader $loader)
    {
        putenv('environmental=' . Snowflake::TASK);

        Snowflake::setTaskId($server->worker_pid);

        $loader->loadByDirectory(MODEL_PATH);

        fire(Event::SERVER_TASK_START);
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @throws Exception
     */
    public function onWorker(Server $server, int $worker_id, Loader $loader)
    {
        Snowflake::setWorkerId($server->worker_pid);
        putenv('environmental=' . Snowflake::WORKER);

        $loader->loadByDirectory(APP_PATH);

        try {
            fire(Event::SERVER_WORKER_START, [$worker_id]);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'throwable');
            write($exception->getMessage(), 'worker');
        }
    }


}
