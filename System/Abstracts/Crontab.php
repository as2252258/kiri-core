<?php


namespace Snowflake\Abstracts;


use Snowflake\Core\Json;
use Snowflake\Process\CrontabProcess;
use Snowflake\Snowflake;
use Exception;


/**
 * Class Crontab
 * @package Snowflake\Abstracts
 */
class Crontab extends Component
{


    /**
     * @param \Snowflake\Crontab $crontab
     * @param $executeTime
     * @throws Exception
     */
    public function dispatch(\Snowflake\Crontab $crontab)
    {
        /** @var CrontabProcess $redis */
        $redis = Snowflake::app()->get(CrontabProcess::class);
        $redis->write(serialize($crontab));
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function clear(string $name)
    {
        /** @var CrontabProcess $redis */
        $redis = Snowflake::app()->get(CrontabProcess::class);
        $redis->write(Json::encode(['action' => 'clear', 'name' => $name]));
    }


    /**
     * @throws Exception
     */
    public function clearAll()
    {
        /** @var CrontabProcess $redis */
        $redis = Snowflake::app()->get(CrontabProcess::class);
        $redis->write(Json::encode(['action' => 'clearAll']));
    }


}
