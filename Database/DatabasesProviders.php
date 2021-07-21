<?php
declare(strict_types=1);

namespace Database;


use Annotation\IAnnotation;
use Exception;
use ReflectionException;
use Server\Constant;
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

    private array $_pooLength = ['min' => 0, 'max' => 1];


    /**
     * @param Application $application
     * @throws Exception
     */
    public function onImport(Application $application)
    {
        $application->set('db', $this);

        $this->_pooLength = Config::get('databases.pool', ['min' => 0, 'max' => 1]);

        Event::on(Event::SERVER_TASK_START, [$this, 'createPool']);
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
        if (!$application->has('databases.' . $name)) {
            $application->set('databases.' . $name, $this->_settings($this->getConfig($name)));
        }
        return $application->get('databases.' . $name);
    }


    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function createPool()
    {
        $databases = Config::get('databases.connections', []);
        if (empty($databases)) {
            return;
        }
        $application = Snowflake::app();
        foreach ($databases as $name => $database) {
            /** @var Connection $connection */
            $application->set('databases.' . $name, $this->_settings($database));
            $application->get('databases.' . $name)->fill();
        }
    }


    /**
     * @param $database
     * @return array
     */
    private function _settings($database): array
    {
        return [
            'class'       => Connection::class,
            'id'          => $database['id'],
            'cds'         => $database['cds'],
            'username'    => $database['username'],
            'password'    => $database['password'],
            'tablePrefix' => $database['tablePrefix'],
            'database'    => $database['database'],
            'maxNumber'   => $this->_pooLength['max'],
            'minNumber'   => $this->_pooLength['min'],
            'charset'     => $database['charset'] ?? 'utf8mb4',
            'slaveConfig' => $database['slaveConfig']
        ];
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
