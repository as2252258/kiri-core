<?php
declare(strict_types=1);

namespace Gii;


use Exception;
use Kiri\Abstracts\Config;
use Kiri\Abstracts\Input;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;

/**
 * Class Command
 * @package Http
 */
class Command extends \Console\Command
{

	public string $command = 'sw:gii';


	public string $description = './snowflake sw:gii make=model|controller|task|interceptor|limits|middleware name=xxxx';


	/**
	 * @param Input $dtl
	 * @return array
	 * @throws Exception
	 */
	public function onHandler(Input $dtl): array
	{
		/** @var Gii $gii */
		$gii = Kiri::app()->get('gii');

		$connections = Kiri::app()->get('db');
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