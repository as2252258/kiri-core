<?php


namespace HttpServer\Events\Trigger;


use HttpServer\Events\Callback;

class OnWorkerStop extends Callback
{


	/**
	 * @param $server
	 * @param $worker_id
	 * @throws \Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$this->clear($server, $worker_id, self::EVENT_STOP);
	}

}
