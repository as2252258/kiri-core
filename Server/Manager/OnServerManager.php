<?php

namespace Server\Manager;

use Server\Abstracts\Server;
use Server\Constant;
use Kiri\Exception\ConfigException;


/**
 * Class OnServerManager
 * @package Server\Manager
 */
class OnServerManager extends Server
{


    /**
     * @param \Swoole\Server $server
     * @throws ConfigException
     */
	public function onManagerStart(\Swoole\Server $server)
	{
        $this->setProcessName(sprintf('manger[%d].0', $server->manager_pid));

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
