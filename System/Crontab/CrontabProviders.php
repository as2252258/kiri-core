<?php


namespace Snowflake\Crontab;


use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;


/**
 * Class CrontabProviders
 * @package Snowflake\Crontab
 */
class CrontabProviders extends Providers
{


    /**
     * @param Application $application
     */
    public function onImport(Application $application)
    {
        $server = $application->getServer();
        if (Config::get('crontab.enable') !== true) {
            return;
        }
        $application->set('crontab', ['class' => Producer::class]);

        $server->addProcess('CrontabZookeeper', ZookeeperProcess::class);
        $server->addProcess('Consumer', Consumer::class);
    }

}
