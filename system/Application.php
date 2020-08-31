<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Snowflake;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\BaseApplication;

/**
 * Class Init
 *
 * @package Snowflake
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
			$https = $this->make(Server::class, Server::class);
			$servers = $https->initCore(Config::get('servers'));
			$process->push($servers);
		}
		if (Config::has('processes', true)) {
			$process->push(Config::get('processes'));
		}
	}


	/**
	 * @param $name
	 * @param $service
	 * @return Application
	 * @throws
	 */
	public function import(string $name, string $service)
	{
		$class = $this->set($name, ['class' => $service]);
		if (method_exists($class, 'onImport')) {
			$class->onImport($this);
		}
		return $this;
	}


	/**
	 * @throws
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
		return make($className, $abstracts);
	}
}
