<?php


namespace HttpServer;


use Console\Dtl;
use Snowflake\Exception\ComponentException;
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
	 * @throws ComponentException
	 */
	public function handler(Dtl $dtl)
	{
		$action = $dtl->get('action', 3);

		/** @var Server $server */
		$server = Snowflake::get()->get('server');
		$server->start();
	}

}
