<?php


namespace Database;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class DatabasesProviders
 * @package Database
 */
class DatabasesProviders extends Component
{


	/**
	 * @param $name
	 * @return DatabasesProviders
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get($name)
	{
		$application = Snowflake::get();
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
