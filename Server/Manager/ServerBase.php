<?php

namespace Server\Manager;

use Server\Abstracts\Server;
use Server\Constant;


/**
 * Class ServerBase
 * @package Server\Manager
 */
class ServerBase extends Server
{


	/**
	 * @param \Swoole\Server $server
	 */
	public function onStart(\Swoole\Server $server)
	{
		$this->runEvent(Constant::START, null, [$server]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onShutdown(\Swoole\Server $server)
	{
		$this->runEvent(Constant::SHUTDOWN, null, [$server]);
	}


	/**
	 * @param \Swoole\Server $server
	 * @param int $src_worker_id
	 * @param mixed $message
	 */
	public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message)
	{
		$this->runEvent(Constant::PIPE_MESSAGE, null, [$server, $src_worker_id, $message]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onBeforeReload(\Swoole\Server $server)
	{
		$this->runEvent(Constant::PIPE_MESSAGE, null, [$server]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onAfterReload(\Swoole\Server $server)
	{
		$this->runEvent(Constant::PIPE_MESSAGE, null, [$server]);
	}

}
