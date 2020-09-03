<?php


namespace Database;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

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
	 * @return DatabasesProviders
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
			'id'          => 'db',
			'cds'         => $config['cds'],
			'username'    => $config['username'],
			'password'    => $config['password'],
			'tablePrefix' => $config['tablePrefix'],
			'maxNumber'   => 100,
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
