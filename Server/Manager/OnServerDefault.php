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
 * Class OnServerDefault
 * @package Server\Manager
 */
class OnServerDefault extends Server
{


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
