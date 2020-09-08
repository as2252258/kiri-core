<?php


namespace Gii;


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

	public $command = 'sw:gii';


	public $description = 'server start|stop|reload|restart';


	/**
	 * @param Input $dtl
	 * @return array
	 * @throws Exception
	 */
	public function onHandler(Input $dtl)
	{
		/** @var Gii $gii */
		$gii = Snowflake::app()->get('gii');

		$connections = Snowflake::app()->db->get($dtl->get('databases', 'db'));

		return $gii->run($connections, $dtl);
	}

}
