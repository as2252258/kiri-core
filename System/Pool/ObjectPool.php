<?php


namespace Snowflake\Pool;


use Exception;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class ObjectPool
 * @package Snowflake\Pool
 */
class ObjectPool extends \Snowflake\Abstracts\Pool
{


    /**
     * set pool max length
     */
    public function init()
    {
        $this->max = 100;
    }


    /**
     * @param array $config
     * @param bool $isMaster
     * @return mixed
     * @throws Exception
     */
    public function getConnection(array $config, bool $isMaster): mixed
    {
        if (is_object($config[0])) {
            $config[0] = get_class($config[0]) ;
        }
        return $this->get(md5($config[0]), $config);
    }


    /**
     * @param string $name
     * @param array $config
     * @return mixed
     * @throws ReflectionException
     * @throws NotFindClassException
     */
    public function createClient(string $name, array $config): mixed
    {
        // TODO: Implement createClient() method.
        return Snowflake::createObject(array_shift($config));
    }


    /**
     * @param string $name
     * @param $object
     */
    public function release(string $name, mixed $object)
    {
        if (method_exists($object, 'clean')) {
            $object->clean();
        }
        $this->push($name, $object);
    }

}
