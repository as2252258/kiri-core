<?php

namespace Server\Manager;

use Exception;
use Server\Abstracts\Server;
use Server\Constant;
use Server\SInterface\PipeMessage;
use Snowflake\Event;


/**
 * Class ServerDefaultEvent
 * @package Server\Manager
 */
class ServerDefaultEvent extends Server
{


    /**
     * @param \Swoole\Server $server
     * @throws \Snowflake\Exception\ConfigException
     */
	public function onStart(\Swoole\Server $server)
	{
        $this->setProcessName(sprintf('start[%d].server', $server->master_pid));

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
	 * @throws Exception
	 */
	public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message)
	{
		if (!is_object($message) || !($message instanceof PipeMessage)) {
			return;
		}
		defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
		$this->runEvent(Constant::PIPE_MESSAGE,
			function (\Swoole\Server $server, $src_worker_id, $message) {
				call_user_func([$message, 'execute']);
			}, [
				$server, $src_worker_id, $message
			]
		);
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
