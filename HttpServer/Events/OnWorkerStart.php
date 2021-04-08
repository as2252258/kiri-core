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
     * @throws Exception
     */
    public function injectLoader($isWorker = false)
    {
        $runtime = file_get_contents(storage('runtime.php'));
        $annotation = Snowflake::app()->getAnnotation();
        $annotation->setLoader(unserialize($runtime));

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
        $annotation = $this->injectLoader();
        (new Pipeline())
            ->if($this->isWorker($worker_id), function ($annotation, $server, $worker_id) {
                $annotation->runtime(CONTROLLER_PATH);
                $annotation->runtime(APP_PATH, CONTROLLER_PATH);
                $this->onWorker($server, $server->worker_id);
                name($server->worker_pid, 'Worker.' . $worker_id);
            })
            ->else(function ($annotation, $server, $worker_id) {
                $annotation->runtime(MODEL_PATH);
                $this->onTask($server, $server->worker_id);
                name($server->worker_pid, 'Task.' . $worker_id);
            })
            ->catch(function (\Throwable $throwable) {
                $this->addError($throwable->getMessage());
            })
            ->before(function ($annotation, $server, $worker_id) {
                putenv('state=start');
                putenv('worker=' . $worker_id);
            })
            ->exec($annotation, $server, $worker_id);
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
