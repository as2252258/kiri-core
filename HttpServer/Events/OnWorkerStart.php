<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Annotation;
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

    private Server $server;


    /**
     * @param $server
     * @param $worker_id
     * @throws ConfigException
     */
    public function actionBefore($server, $worker_id)
    {
    }


    /**
     * @return Annotation
     * @throws Exception
     */
    public function injectLoader($isWorker = false): Annotation
    {
        $runtime = file_get_contents(storage('runtime.php'));
        $annotation = Snowflake::app()->getAnnotation();
        $annotation->setLoader(unserialize($runtime));
        if ($isWorker === true) {
            $annotation->runtime(CONTROLLER_PATH);
            $annotation->runtime(APP_PATH, CONTROLLER_PATH);

            name($this->server->worker_pid, 'Worker.' . $this->server->worker_id);
        } else {
            $annotation->runtime(MODEL_PATH);

            name($this->server->worker_pid, 'Task.' . $this->server->worker_id);
        }
        return $annotation;
    }


    /**
     * @param Server $server
     * @param int $worker_id
     *
     * @return mixed
     * @throws Exception
     */
    public function onHandler(Server $server, int $worker_id): void
    {
        $this->server = $server;

        $this->injectLoader($this->isWorker($worker_id));

        putenv('state=start');
        putenv('worker=' . $server->worker_id);


        if ($worker_id < $server->setting['worker_num']) {
            $this->onWorker($server, $worker_id);
        } else {
            $this->onTask($server, $worker_id);
        }
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @return bool
     */
    private function isWorker(int $worker_id): bool
    {
        return $worker_id < $this->server->setting['worker_num'];
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
