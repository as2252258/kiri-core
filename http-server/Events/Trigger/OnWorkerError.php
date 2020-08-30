<?php


namespace HttpServer\Events\Trigger;


use HttpServer\Events\Callback;

class OnWorkerError extends Callback
{

	/**
	 * @param $server
	 * @param $worker_id
	 * @throws \Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$this->clear($server, $worker_id, self::EVENT_ERROR);
	}

}
