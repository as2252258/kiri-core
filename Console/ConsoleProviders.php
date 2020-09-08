<?php

namespace Console;


use Exception;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;

/**
 * Class ConsoleProviders
 * @package Console
 */
class ConsoleProviders extends Providers
{

	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		$application->set('console', ['class' => Console::class]);
	}


}
