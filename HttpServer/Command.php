<?php


namespace HttpServer;


use Console\Dtl;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class Command
 * @package HttpServer
 */
class Command extends \Console\Command
{

	public $command = 'server';


	public $description = 'server start|stop|reload|restart';


	private $actions = [
		'start', 'stop', 'reload', 'restart'
	];


	/**
	 * @param Dtl $dtl
	 * @throws ComponentException|ConfigException
	 * @throws \Exception
	 */
	public function handler(Dtl $dtl)
	{
		$action = $dtl->get('action', 3);

		/** @var Server $server */
		$server = Snowflake::app()->get('server');
		switch ($action) {
			case 'restart':
				$server->shutdown();
				$server->start();
				break;
			case 'stop':
				$server->shutdown();
				break;
			case 'start':
			default:
				$server->start();
		}
	}

}
