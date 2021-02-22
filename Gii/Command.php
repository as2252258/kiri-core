<?php
declare(strict_types=1);

namespace Gii;


use Exception;
use Snowflake\Abstracts\Config;
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


	public string $description = './snowflake sw:gii make=model table=xxxx databases=xxx' . "\v\f\t" .
	'./snowflake sw:gii make=controller table=xxxx databases=xxx' . "\v\f\t" .
	'./snowflake sw:gii make=task name=xxxx' . "\v\f\t" .
	'./snowflake sw:gii make=interceptor name=xxxx' . "\v\f\t" .
	'./snowflake sw:gii make=limits name=xxxx' . "\v\f\t" .
	'./snowflake sw:gii make=middleware name=xxxx' . "\v\f\t";


	/**
	 * @param Input $dtl
	 * @return array
	 * @throws Exception
	 */
	public function onHandler(Input $dtl): array
	{
		/** @var Gii $gii */
		$gii = Snowflake::app()->get('gii');

		$connections = Snowflake::app()->db;
		if ($dtl->exists('databases')) {
			return $gii->run($connections->get($dtl->get('databases')), $dtl);
		}

		$array = [];
		foreach (Config::get('databases') as $key => $connection) {
			$array[$key] = $gii->run($connections->get($key), $dtl);
		}
		return $array;
	}

}
