<?php
declare(strict_types=1);

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

	public string $command = 'sw:gii';


	public string $description = 'server start|stop|reload|restart';


	/**
	 * @param Input $dtl
	 * @return array
	 * @throws Exception
	 */
	public function onHandler(Input $dtl): array
	{
		/** @var Gii $gii */
		$gii = Snowflake::app()->get('gii');

		$connections = Snowflake::app()->db->get($dtl->get('databases', 'db'));

		return $gii->run($connections, $dtl);
	}

}
