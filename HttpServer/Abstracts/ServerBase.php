<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:37
 */
declare(strict_types=1);
namespace HttpServer\Abstracts;


use Swoole\WebSocket\Server;

/**
 * Class OnServerDefault
 * @package Snowflake\Snowflake\Server
 */
abstract class ServerBase extends HttpService
{

	/** @var Server */
	protected Server $server;

	/**
	 * @return Server
	 */
	public function getServer(): Server
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
