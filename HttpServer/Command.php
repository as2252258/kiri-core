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

		$manager->shutdown();
		if ($dtl->get('action') == 'stop') {
			return 'shutdown success.';
		}

		$this->scan_system_annotation();

		return $manager->start();
	}


	/**
	 * @throws ReflectionException
	 * @throws ComponentException
	 * @throws NotFindPropertyException
	 */
	public function scan_system_annotation()
	{
		$annotation = Snowflake::app()->getAttributes();
		$annotation->readControllers(__DIR__ . '/../Console', 'Console', 'system');
		$annotation->readControllers(__DIR__ . '/../Database', 'Database', 'system');
		$annotation->readControllers(__DIR__ . '/../Gii', 'Gii', 'system');
		$annotation->readControllers(__DIR__ . '/../HttpServer', 'HttpServer', 'system');
		$annotation->readControllers(__DIR__ . '/../Kafka', 'Kafka', 'system');
		$annotation->readControllers(__DIR__ . '/../System', 'Snowflake', 'system');
		$annotation->readControllers(__DIR__ . '/../Validator', 'Validator', 'system');
	}


}
