<?php
declare(strict_types=1);

namespace HttpServer;


use Console\Console;
use Exception;
use Snowflake\Abstracts\Providers;

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

		/** @var Console $console */
		$console = $application->get('console');
		$console->register(Command::class);
	}
}
