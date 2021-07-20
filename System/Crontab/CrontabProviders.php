<?php


namespace Snowflake\Crontab;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;


/**
 * Class CrontabProviders
 * @package Snowflake\Crontab
 */
class CrontabProviders extends Providers
{


	/**
	 * @param Application $application
	 * @throws ConfigException
	 * @throws Exception
	 */
    public function onImport(Application $application)
    {
        $server = $application->getServer();
        $application->set('crontab', ['class' => Producer::class]);
        if (Config::get('crontab.enable') !== true) {
            return;
        }
        $server->addProcess(Zookeeper::class);
    }

}
