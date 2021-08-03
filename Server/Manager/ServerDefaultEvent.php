<?php

namespace Server\Manager;

use Annotation\Inject;
use Exception;
use Server\Abstracts\Server;
use Server\Constant;
use Server\Events\OnAfterRequest;
use Server\SInterface\PipeMessage;
use Snowflake\Event;
use Snowflake\Events\EventDispatch;
use Snowflake\Exception\ConfigException;


/**
 * Class ServerDefaultEvent
 * @package Server\Manager
 */
class ServerDefaultEvent extends Server
{


	/** @var EventDispatch  */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


    /**
     * @param \Swoole\Server $server
     * @throws ConfigException
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
		defer(fn() => $this->eventDispatch->dispatch(new OnAfterRequest()));
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
