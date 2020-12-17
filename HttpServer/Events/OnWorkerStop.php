<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;

/**
 * Class OnWorkerStop
 * @package HttpServer\Events
 */
class OnWorkerStop extends Callback
{


	/**
	 * @param $server
	 * @param $worker_id
	 * @throws Exception
	 */
	public function onHandler($server, $worker_id)
	{
		$this->clear($server, $worker_id, self::EVENT_STOP);
	}

}
