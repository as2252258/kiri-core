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
		$name = 'databases.' . $name;
		$config = Config::get('databases.' . $name, true);

		$application = Snowflake::get();
		if (!$application->has($name)) {
			$generate = [
				'class'       => Connection::class,
				'id'          => 'db',
				'cds'         => $config['cds'],
				'username'    => $config['username'],
				'password'    => $config['password'],
				'tablePrefix' => $config['tablePrefix'],
				'maxNumber'   => 100,
				'slaveConfig' => $config['slaveConfig']
			];
			return $application->set($name, $generate);
		} else {
			return $application->get($name);
		}
	}


}
