<?php


namespace Kiri;


use Exception;
use Kiri\Abstracts\Component;
use Server\ServerManager;

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
    	$context  = di(ServerManager::class);
    	$context->task(static::$_absences[$name], $params);
    }

}