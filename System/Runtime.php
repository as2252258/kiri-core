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


	/**
	 * @param Input $dtl
	 * @throws Exception
	 */
    public function onHandler(Input $dtl)
    {
        // TODO: Implement onHandler() method.
        $annotation = Snowflake::app()->getAnnotation();

        $runtime = storage(static::CACHE_NAME);

        Snowflake::writeFile($runtime, serialize($annotation->getLoader()));
    }

}
