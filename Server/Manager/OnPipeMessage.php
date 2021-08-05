<?php

namespace Server\Manager;

use Annotation\Inject;
use Kafka\Message;
use Server\Abstracts\Server;
use Server\Constant;
use Exception;
use Server\Events\OnAfterRequest;
use Server\SInterface\PipeMessage;
use Snowflake\Events\EventDispatch;

/**
 *
 */
class OnPipeMessage extends Server
{


	/** @var EventDispatch  */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


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

		call_user_func([$message, 'process'], $server, $src_worker_id);
		$this->eventDispatch->dispatch(new OnAfterRequest());
	}


}
