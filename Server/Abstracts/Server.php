<?php


namespace Server\Abstracts;


use Closure;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Event;
use Kiri\Exception\ConfigException;
use Kiri\Kiri;
use Swoole\Server\Port;


/**
 * Class Server
 * @package Server\Abstracts
 */
abstract class Server
{


	/**
	 * @param $prefix
	 * @throws ConfigException
	 */
	protected function setProcessName($prefix)
	{
		if (Kiri::getPlatform()->isMac()) {
			return;
		}
		$name = Config::get('id', 'system-service');
		if (!empty($prefix)) {
			$name .= '.' . $prefix;
		}
		swoole_set_process_name($name);
	}


	/**
	 * Server constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
	}

}
