<?php
declare(strict_types=1);
namespace Console;


use Exception;
use Kiri\Abstracts\Providers;
use Kiri\Application;

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
		/** @var Console $console */
		$application->set('console', ['class' => Console::class]);
	}


}
