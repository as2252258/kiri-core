<?php
declare(strict_types=1);

namespace HttpServer;


use Exception;
use ReflectionException;
use Snowflake\Abstracts\Input;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindPropertyException;
use Snowflake\Process\Process;
use Snowflake\Snowflake;

/**
 * Class Command
 * @package HttpServer
 */
class Command extends \Console\Command
{

	public string $command = 'sw:server';


	public string $description = 'server start|stop|reload|restart';


	const ACTIONS = ['start', 'stop', 'restart'];


	/**
	 * @param Input $dtl
	 * @return string
	 * @throws Exception
	 * @throws ConfigException
	 */
	public function onHandler(Input $dtl): string
	{
		$manager = Snowflake::app()->getServer();
		$manager->setDaemon($dtl->get('daemon', 0));

		if (!in_array($dtl->get('action'), self::ACTIONS)) {
			return 'I don\'t know what I want to do.';
		}

		if ($manager->isRunner() && $dtl->get('action') == 'start') {
			return 'Service is running. Please use restart.';
		}

		Process::kill(Snowflake::getMasterPid(), SIGTERM);
		if ($dtl->get('action') == 'stop') {
			return 'shutdown success.';
		}

		return $manager->start();
	}

}
