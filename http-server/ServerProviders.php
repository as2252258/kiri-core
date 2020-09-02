<?php


namespace HttpServer;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\Component;
use Snowflake\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class DatabasesProviders
 * @package Database
 */
class ServerProviders extends Component
{

	/**
	 * @throws Exception
	 */
	public function onImport()
	{
		Snowflake::get()->set('server', [
			'class' => Server::class
		]);
	}
}
