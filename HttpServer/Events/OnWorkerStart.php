<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Annotation;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{

    /**
     * @throws Exception
     */
    public function injectLoader($isWorker = false)
    {
        $runtime = file_get_contents(storage('runtime.php'));
        $annotation = Snowflake::app()->getAnnotation();
        $annotation->setLoader(unserialize($runtime));
        if ($isWorker === true) {
            $annotation->runtime(CONTROLLER_PATH);
            $annotation->runtime(APP_PATH, CONTROLLER_PATH);
        } else {
            $annotation->runtime(MODEL_PATH);
        }
        return $isWorker;
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
        putenv('state=start');
        putenv('worker=' . $worker_id);

        $isWorker = $this->injectLoader($this->isWorker($server, $worker_id));

        $this->{$isWorker ? 'onWorker' : 'onTask'}($server);
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @return bool
     */
    private function isWorker($server, int $worker_id): bool
    {
        return $worker_id < $server->setting['worker_num'];
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @throws Exception
     */
    public function onTask(Server $server)
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
    public function onWorker(Server $server)
    {
        Snowflake::setWorkerId($server->worker_pid);
        putenv('environmental=' . Snowflake::WORKER);

        try {
            fire(Event::SERVER_WORKER_START, [getenv('worker')]);
        } catch (\Throwable $exception) {
            $this->addError($exception, 'throwable');
            write($exception->getMessage(), 'worker');
        }
    }


}
