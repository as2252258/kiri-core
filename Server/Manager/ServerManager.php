<?php

namespace Server\Manager;

use Server\Abstracts\Server;
use Server\Constant;


/**
 * Class ServerManager
 * @package Server\Manager
 */
class ServerManager extends Server
{


	/**
	 * @param \Swoole\Server $server
	 */
	public function onManagerStart(\Swoole\Server $server)
	{
		$this->runEvent(Constant::MANAGER_START, null, [$server]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onManagerStop(\Swoole\Server $server)
	{
		$this->runEvent(Constant::MANAGER_STOP, null, [$server]);
	}


}
