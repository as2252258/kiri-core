<?php

namespace Server\Manager;

use Annotation\Inject;
use Server\Abstracts\Server;
use Server\Events\OnShutdown;
use Server\Events\OnStart;
use Kiri\Events\EventDispatch;
use Kiri\Exception\ConfigException;


/**
 * Class OnServerDefault
 * @package Server\Manager
 */
class OnServer extends Server
{

	/**
	 * @var EventDispatch
	 */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	/**
     * @param \Swoole\Server $server
     * @throws ConfigException
     */
	public function onStart(\Swoole\Server $server)
	{
        $this->setProcessName(sprintf('start[%d].server', $server->master_pid));

        $this->eventDispatch->dispatch(new OnStart($server));
	}


	
	/**
	 * @param \Swoole\Server $server
	 */
	public function onShutdown(\Swoole\Server $server)
	{
		$this->eventDispatch->dispatch(new OnShutdown($server));
	}



}
