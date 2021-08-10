<?php


namespace Kiri;


use Exception;
use HttpServer\IInterface\Task;
use ReflectionException;
use Kiri\Abstracts\Component;

/**
 * Class Async
 * @package Kiri
 */
class Async extends Component
{


    private static array $_absences = [];


    /**
     * @param string $name
     * @param Task $handler
     */
    public function addAsync(string $name, Task $handler)
    {
        static::$_absences[$name] = $handler::class;
    }


    /**
     * @param string $name
     * @param array $params
     * @throws Exception
     */
    public function dispatch(string $name, array $params = [])
    {
        $server = Kiri::app()->getSwoole();
        if (!isset($server->setting['task_worker_num'])) {
            return;
        }

        if (!isset(static::$_absences[$name])) {
            return;
        }

        /** @var Task $class */
        $class = Kiri::createObject(static::$_absences[$name]);
        $class->setParams($params);

        $randWorkerId = random_int(0, $server->setting['task_worker_num'] - 1);

        $server->task(serialize($class), $randWorkerId);
    }

}
