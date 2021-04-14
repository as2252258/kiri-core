<?php


namespace Snowflake\Crontab;


use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;
use Exception;


/**
 * Class Producer
 * @package Snowflake\Abstracts
 */
class Producer extends Component
{

    const CRONTAB_KEY = 'system:crontab';


	/**
	 * @param Crontab $crontab
	 * @throws Exception
	 */
    public function dispatch(Crontab $crontab)
    {
        $redis = Snowflake::app()->getRedis();

        $name = $crontab->getName();

        $redis->set('crontab:' . $name, swoole_serialize($crontab));

        $tickTime = time() + $crontab->getTickTime();

        $result = $redis->zAdd(self::CRONTAB_KEY, $tickTime, $name);
        var_dump($result, $crontab);
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
            $redis->del('crontab:' . $datum);
        }
        $redis->release();
    }


}
