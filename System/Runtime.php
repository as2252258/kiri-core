<?php


namespace Snowflake;


use Console\Command;
use Exception;
use Snowflake\Abstracts\Input;


/**
 * Class Runtime
 * @package Snowflake
 */
class Runtime extends Command
{


    public string $command = 'runtime:builder';

    public string $description = 'create app file cache';


    const CACHE_NAME = '.runtime.cache';
    const CONFIG_NAME = '.config.cache';


	/**
	 * @param Input $dtl
	 * @throws Exception
	 */
    public function onHandler(Input $dtl)
    {
        // TODO: Implement onHandler() method.
        $annotation = Snowflake::app()->getAnnotation();

        $runtime = storage(static::CACHE_NAME);

        $configs = Snowflake::app()->getConfig()->getData();
	    array_walk_recursive($configs, function (&$value, $key) {
		    if ($value instanceof \Closure) {
			    $value = null;
		    }
	    });

        Snowflake::writeFile(storage(static::CONFIG_NAME), serialize($configs));
        Snowflake::writeFile($runtime, serialize($annotation->getLoader()));
    }

}
