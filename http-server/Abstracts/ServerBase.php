<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */

namespace HttpServer\Abstracts;


use HttpServer\Application;
use Swoole\WebSocket\Server;

/**
 * Class ServerBase
 * @package Snowflake\Snowflake\Server
 */
abstract class ServerBase extends Application
{

	/** @var Server */
	protected $server;

	/**
	 * @return Server
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * @param $server
	 */
	public function setServer($server)
	{
		$this->server = $server;
	}

}
