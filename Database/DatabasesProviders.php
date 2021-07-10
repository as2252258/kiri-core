<?php
declare(strict_types=1);

namespace Database;


use Annotation\IAnnotation;
use Exception;
use ReflectionException;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Exception\NotFindPropertyException;
use Snowflake\Snowflake;
use Snowflake\Abstracts\Config;

/**
 * Class DatabasesProviders
 * @package Database
 */
class DatabasesProviders extends Providers
{


    /**
     * @param Application $application
     * @throws Exception
     */
    public function onImport(Application $application)
    {
        $application->set('db', $this);

        Event::on(Event::SERVER_TASK_START, [$this, 'createPool']);
        Event::on(Event::SERVER_WORKER_START, [$this, 'createPool']);
    }


    /**
     * @param $name
     * @return Connection
     * @throws ConfigException
     * @throws Exception
     */
    public function get($name): Connection
    {
        $application = Snowflake::app();
        if ($application->has('databases.' . $name)) {
            return $application->get('databases.' . $name);
        }
        $config = $this->getConfig($name);

        $max = Config::get('databases.pool.max', 30);
        return $application->set('databases.' . $name, [
            'class'       => Connection::class,
            'id'          => $config['id'],
            'cds'         => $config['cds'],
            'username'    => $config['username'],
            'password'    => $config['password'],
            'tablePrefix' => $config['tablePrefix'],
            'maxNumber'   => $max,
            'database'    => $config['database'],
            'charset'     => $config['charset'] ?? 'utf8mb4',
            'slaveConfig' => $config['slaveConfig']
        ]);
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function createPool()
    {
        $databases = Config::get('databases.connections', []);
        var_dump($databases);
        if (empty($databases)) {
            return;
        }

        $max = Config::get('databases.pool', ['max' => 10, 'min' => 10]);

        $application = Snowflake::app();
        foreach ($databases as $name => $database) {
            /** @var Connection $connection */
            $connection = $application->set('databases.' . $name, [
                'class'       => Connection::class,
                'id'          => $database['id'],
                'cds'         => $database['cds'],
                'username'    => $database['username'],
                'password'    => $database['password'],
                'tablePrefix' => $database['tablePrefix'],
                'database'    => $database['database'],
                'maxNumber'   => $max['max'],
                'minNumber'   => $max['min'],
                'charset'     => $database['charset'] ?? 'utf8mb4',
                'slaveConfig' => $database['slaveConfig']
            ]);
            $connection->fill();
        }
    }


    /**
     * @param $name
     * @return mixed
     * @throws ConfigException
     */
    public function getConfig($name): mixed
    {
        return Config::get('databases.connections.' . $name, null, true);
    }


}
