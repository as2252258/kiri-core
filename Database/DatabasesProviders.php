<?php


namespace Database;


use Exception;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;
use Snowflake\Exception\ConfigException;
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
	}


	/**
	 * @param $name
	 * @return Connection
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get($name)
	{
		$application = Snowflake::app();
		if ($application->has('databases.' . $name)) {
			return $application->get('databases.' . $name);
		}
		$config = $this->getConfig($name);
		return $application->set('databases.' . $name, [
			'class'       => Connection::class,
			'id'          => $config['id'],
			'cds'         => $config['cds'],
			'username'    => $config['username'],
			'password'    => $config['password'],
			'tablePrefix' => $config['tablePrefix'],
			'maxNumber'   => $config['maxNumber'],
			'slaveConfig' => $config['slaveConfig']
		]);
	}


	/**
	 * @param $name
	 * @return array|mixed|null
	 * @throws ConfigException
	 */
	public function getConfig($name)
	{
		return Config::get('databases.' . $name, true);
	}


}
