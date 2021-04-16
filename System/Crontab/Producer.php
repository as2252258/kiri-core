<?php


namespace Snowflake\Crontab;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * Class Producer
 * @package Snowflake\Abstracts
 */
class Producer extends Component
{

    const CRONTAB_KEY = '_application:system:crontab';


    /**
     * @param Crontab $crontab
     * @throws Exception
     */
    public function dispatch(Crontab $crontab)
    {
        $redis = Snowflake::app()->getRedis();

        $name = $crontab->getName();
        if ($redis->exists(self::CRONTAB_KEY) && $redis->type(self::CRONTAB_KEY) !== \Redis::REDIS_ZSET) {
            throw new Exception('Cache key ' . self::CRONTAB_KEY . ' types error.');
        }

	    $redis->del('stop:crontab:' . $name);

	    $redis->del('crontab:' . $name);
        $redis->zRem(static::CRONTAB_KEY, $name);

        $redis->zAdd(self::CRONTAB_KEY, time() + $crontab->getTickTime(), $name);
        $redis->set('crontab:' . $name, swoole_serialize($crontab));
    }


    /**
     * @param string $name
     * @throws Exception
     */
    public function clear(string $name)
    {
        $redis = Snowflake::app()->getRedis();

	    $redis->del('crontab:' . md5($name));
	    $redis->zRem(static::CRONTAB_KEY, md5($name));

	    $redis->setex('stop:crontab:' . md5($name), 10, 1);
    }


    /**
     * @throws Exception
     */
    public function clearAll()
    {
        $redis = Snowflake::app()->getRedis();
        $data = $redis->zRange(self::CRONTAB_KEY, 0, -1);
        foreach ($data as $datum) {
            $redis->setex('stop:crontab:' . $datum, 10, 1);
            $redis->del('crontab:' . $datum);
        }
        $redis->release();
    }


}
