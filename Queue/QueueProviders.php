<?php

declare(strict_types=1);

namespace Queue;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;


/**
 * Class QueueProviders
 * @package Queue
 */
class QueueProviders extends Providers
{

	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		/** @var Server $server */
		$server = $application->get('server');
		$server->addProcess('queue', Queue::class);
	}

}
