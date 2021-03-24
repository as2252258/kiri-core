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

    const CRONTAB_KEY = 'system:crontab';


    /**
     * @param \Snowflake\Crontab $crontab
     * @param $executeTime
     * @throws Exception
     */
    public function dispatch(\Snowflake\Crontab $crontab)
    {
        $redis = Snowflake::app()->getRedis();

        $name = md5($crontab->getName());

        $redis->set('crontab:' . $name, swoole_serialize($crontab));

        $tickTime = time() + $crontab->getTickTime();

        $redis->zAdd(self::CRONTAB_KEY, $tickTime, $crontab->getName());
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function clear(string $name)
    {
        $redis = Snowflake::app()->getRedis();

        $redis->zRem(self::CRONTAB_KEY, $name);
        $redis->del('crontab:' . md5($name));
    }


    /**
     * @throws Exception
     */
    public function clearAll()
    {
        $redis = Snowflake::app()->getRedis();
        $data = $redis->zRange(self::CRONTAB_KEY, 0, -1);
        $redis->del(self::CRONTAB_KEY);
        foreach ($data as $datum) {
            $redis->del('crontab:' . md5($datum));
        }
        $redis->release();
    }


}
