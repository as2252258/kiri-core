<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;

class OnWorkerExit extends Callback
{

	/**
	 * @param $server
	 * @param $worker_id
	 * @throws \Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$this->clear($server, $worker_id, self::EVENT_EXIT);
	}

}
