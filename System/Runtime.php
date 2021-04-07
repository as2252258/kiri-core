<?php


namespace Snowflake;


use Console\Command;
use Snowflake\Abstracts\Input;


/**
 * Class Runtimer
 * @package Snowflake
 */
class Runtime extends Command
{


    public string $command = 'runtime:builder';


    /**
     * @param Input $dtl
     */
    public function onHandler(Input $dtl)
    {
        // TODO: Implement onHandler() method.

        $annotation = Snowflake::app()->getAnnotation();
        $annotation->read(APP_PATH, 'App');

        $runtime = storage('runtime.php');

        Snowflake::writeFile($runtime, serialize($annotation->getLoader()));
    }

}
