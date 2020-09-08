<?php


namespace Gii;


use Console\Console;
use Exception;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;

/**
 * Class DatabasesProviders
 * @package Database
 */
class GiiProviders extends Providers
{


	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		$application->set('gii', ['class' => Gii::class]);

		/** @var Console $console */
		$console = $application->get('console');
		$console->register(Command::class);
	}
}
