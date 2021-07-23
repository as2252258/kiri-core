<?php

namespace Server\Manager;

use Server\Abstracts\Server;
use Server\Constant;
use Server\SInterface\PipeMessage;


/**
 * Class ServerDefaultEvent
 * @package Server\Manager
 */
class ServerDefaultEvent extends Server
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
		if (is_null($message = unserialize($message))) {
			return;
		}
		if (!is_object($message) || !($message instanceof PipeMessage)) {
			return;
		}
		$this->runEvent(Constant::PIPE_MESSAGE, null, [$server, $src_worker_id, $message]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onBeforeReload(\Swoole\Server $server)
	{
		$this->runEvent(Constant::BEFORE_RELOAD, null, [$server]);
	}


	/**
	 * @param \Swoole\Server $server
	 */
	public function onAfterReload(\Swoole\Server $server)
	{
		$this->runEvent(Constant::AFTER_RELOAD, null, [$server]);
	}

}
