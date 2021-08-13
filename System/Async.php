<?php


namespace Kiri;


use Exception;
use HttpServer\IInterface\Task;
use ReflectionException;
use Kiri\Abstracts\Component;
use Server\ServerManager;
use Server\SInterface\TaskExecute;

/**
 * Class Async
 * @package Kiri
 */
class Async extends Component
{


    private static array $_absences = [];


	/**
	 * @param string $name
	 * @param TaskExecute $handler
	 */
    public function addAsync(string $name, TaskExecute $handler)
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
    	$context  = ServerManager::getContext();
    	$context->task(static::$_absences[$name], $params);
    }

}
