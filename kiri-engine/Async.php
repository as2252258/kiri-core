<?php


namespace Kiri;


use Exception;
use Kiri\Abstracts\Component;
use Kiri\Server\ServerManager;
use Kiri\Server\Tasker\AsyncTaskExecute;
use Kiri;
/**
 * Class Async
 * @package Kiri
 */
class Async extends Component
{


    private static array $_absences = [];


	/**
	 * @param string $name
	 * @param string $handler
	 */
    public function addAsync(string $name, string $handler)
    {
        static::$_absences[$name] = $handler;
    }


    /**
     * @param string $name
     * @param array $params
     * @throws Exception
     */
    public function dispatch(string $name, array $params = [])
    {
    	$context = di(AsyncTaskExecute::class);
    	$context->execute(static::$_absences[$name], $params);
    }

}
