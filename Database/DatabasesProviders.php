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
		$config = Config::get('databases.' . $name, true);
		if (Snowflake::get()->has($name)) {
			return Snowflake::get()->get($name);
		}
		return Snowflake::get()->set($name, [
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


}
