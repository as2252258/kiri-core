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
	 * @throws \Exception
	 */
    public function onHandler(Input $dtl)
    {
        // TODO: Implement onHandler() method.

        $annotation = Snowflake::app()->getAnnotation();
        $annotation->read(directory('app'), 'App');

        $runtime = storage('runtime.php');

        Snowflake::writeFile($runtime, serialize($annotation->getLoader()));
    }

}
