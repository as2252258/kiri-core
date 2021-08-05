<?php


namespace Server\Task;


use ReflectionException;
use Server\SInterface\TaskExecute;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class OnServerTask
 * @package Server\Task
 */
class OnServerTask
{


    /**
     * @param Server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param mixed $data
     */
    public function onTask(Server $server, int $task_id, int $src_worker_id, mixed $data)
    {
        try {
            $data = $this->resolve($data);
        } catch (\Throwable $exception) {
            $data = [$exception->getMessage()];
        } finally {
            $server->finish($data);
        }
    }


    /**
     * @param Server $server
     * @param Server\Task $task
     */
    public function onCoroutineTask(Server $server, Server\Task $task)
    {
        try {
            $data = $this->resolve($task->data);
        } catch (\Throwable $exception) {
            $data = [$exception->getMessage()];
        } finally {
            $server->finish($data);
        }
    }


    /**
     * @param $data
     * @return null
     * @throws ReflectionException
     * @throws NotFindClassException
     */
    private function resolve($data)
    {
        [$class, $params] = json_encode($data, true);

        $reflect = Snowflake::getDi()->getReflect($class);

        if (!$reflect->isInstantiable()) {
            return null;
        }
        $class = $reflect->newInstanceArgs($params);
        return $class->execute();
    }


    /**
     * @param Server $server
     * @param int $task_id
     * @param mixed $data
     */
    public function onFinish(Server $server, int $task_id, mixed $data)
    {
        if (!($data instanceof TaskExecute)) {
            return;
        }
        $data->finish($server, $task_id);
    }


}
