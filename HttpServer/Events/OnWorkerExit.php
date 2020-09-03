<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;

/**
 * Class OnWorkerExit
 * @package HttpServer\Events
 */
class OnWorkerExit extends Callback
{

	/**
	 * @param $server
	 * @param $worker_id
	 * @throws Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$this->clear($server, $worker_id, self::EVENT_EXIT);
	}

}
