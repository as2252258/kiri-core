<?php


namespace HttpServer;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Providers;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class DatabasesProviders
 * @package Database
 */
class ServerProviders extends Providers
{


	/**
	 * @param \Snowflake\Application $application
	 * @throws Exception
	 */
	public function onImport(\Snowflake\Application $application)
	{
		$application->set('server', ['class' => Server::class]);

//		/** @var \Console\Application $console */
//		$console = $application->get('console');
//		$console->register(Command::class);
	}
}
