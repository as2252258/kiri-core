<?php


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

	public $command = 'sw:server';


	public $description = 'server start|stop|reload|restart';


	/**
	 * @param Input $dtl
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function onHandler(Input $dtl)
	{
		$manager = Snowflake::app()->server;
		$manager->setDaemon($dtl->get('daemon', 0));
		switch ($dtl->get('action')) {
			case 'stop':
				$manager->shutdown();
				break;
			case 'restart':
				$manager->shutdown();
				$manager->start();
				break;
			case 'start':
				$manager->start();
				break;
			default:
				$this->error('I don\'t know what I want to do.');
		}
	}

}
