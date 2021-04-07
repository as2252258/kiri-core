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

        $annotation = Snowflake::app()->getAnnotation();

        /** @var Loader $runtime */
        $runtime = unserialize(file_get_contents(storage('runtime.php')));
        $annotation->setLoader($runtime);

        if ($worker_id >= $server->setting['worker_num']) {
            $annotation->instanceDirectoryFiles(MODEL_PATH);

            $this->onTask($server, $worker_id);
        } else {

            $start = microtime(true);

            $annotation->instanceDirectoryFiles(APP_PATH);

            $this->error('use time ' . (microtime(true) - $start));

            $this->onWorker($server, $worker_id);
        }
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @throws Exception
     */
    public function onTask(Server $server, int $worker_id)
    {
        putenv('environmental=' . Snowflake::TASK);

        Snowflake::setTaskId($server->worker_pid);

        fire(Event::SERVER_TASK_START);
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @throws Exception
     */
    public function onWorker(Server $server, int $worker_id)
    {
        Snowflake::setWorkerId($server->worker_pid);
        putenv('environmental=' . Snowflake::WORKER);

        try {
            fire(Event::SERVER_WORKER_START, [$worker_id]);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'throwable');
            write($exception->getMessage(), 'worker');
        }
    }


}
