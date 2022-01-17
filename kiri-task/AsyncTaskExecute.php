<?php

namespace Kiri\Task;

use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use Kiri\Server\SwooleServerInterface;


/**
 *
 */
class AsyncTaskExecute extends Component
{


	use TaskResolve;



	/**
	 * @param OnTaskInterface|string $handler
	 * @param array $params
	 * @param int $workerId
	 * @throws Exception
	 */
	public function execute(OnTaskInterface|string $handler, array $params = [], int $workerId = -1)
	{
		$server = Kiri::getDi()->get(SwooleServerInterface::class);
		if ($workerId < 0 || $workerId > $server->setting['task_worker_num']) {
			$workerId = random_int(0, $server->setting['task_worker_num'] - 1);
		}
		if (is_string($handler)) {
			$handler = $this->handle($handler, $params);
		}
		$server->task(serialize($handler), $workerId);
	}


}
