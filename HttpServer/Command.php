<?php
declare(strict_types=1);

namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\ConfigException;
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

		/** @var Shutdown $shutdown */
		$shutdown = Snowflake::app()->get('shutdown');
		if ($shutdown->isRunning() && $dtl->get('action') == 'start') {
			return 'Service is running. Please use restart.';
		}
		$shutdown->shutdown();
		if ($dtl->get('action') == 'stop') {
			return 'shutdown success.';
		}

		$this->generate_runtime_builder();
		return $manager->start();
	}


	/**
	 *
	 */
	private function generate_runtime_builder()
	{
		exec(PHP_BINARY . ' ' . APP_PATH . 'snowflake runtime:builder');
	}

}
