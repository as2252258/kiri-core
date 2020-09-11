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
	 * @return mixed|void
	 */
	public function onHandler(Input $dtl)
	{
		$manager = Snowflake::app()->server;
		$manager->setDaemon($dtl->get('daemon', 0));
		if ($manager->isRunner()) {
			$manager->shutdown();
		}
		if ($dtl->get('action') == 'stop') {
			return;
		}
		if (!in_array($dtl->get('action'), ['start', 'restart'])) {
			return $this->error('I don\'t know what I want to do.');
		}
		$manager->start();
	}

}
