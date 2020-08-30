<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Snowflake;


use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Server;
use Snowflake\Abstracts\BaseApplication;

/**
 * Class Init
 *
 * @package BeReborn\Web
 *
 * @property-read Config $config
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$process = Snowflake::get()->processes;
		if (Config::has('servers', true)) {
			/** @var Server $https */
			$https = $this->make(Server::class);
			$servers = $https->initCore(Config::get('servers'));
			$process->push($servers);
		}
		if (Config::has('processes', true)) {
			$process->push(Config::get('processes'));
		}
	}


	/**
	 * @throws Exception
	 */
	public function start()
	{
		$process = Snowflake::get()->processes;
		$process->start();
	}


	/**
	 * @param $className
	 * @param null $abstracts
	 * @return mixed
	 * @throws Exception
	 */
	public function make($className, $abstracts = null)
	{
		return Snowflake::createObject($className);
	}
}
