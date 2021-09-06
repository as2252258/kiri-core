<?php

namespace Server\Worker;

use Annotation\Inject;
use Exception;
use Kiri\Abstracts\Config;
use Kiri\Core\Help;
use Kiri\Events\EventDispatch;
use Kiri\Runtime;
use Server\Events\OnAfterWorkerStart;
use Server\Events\OnBeforeWorkerStart;
use Server\Events\OnWorkerError;
use Server\Events\OnWorkerExit;
use Server\Events\OnWorkerStart;
use Server\Events\OnWorkerStop;
use Server\ServerManager;
use Swoole\Server;
use Swoole\Timer;


/**
 * Class OnServerWorker
 * @package Server\Worker
 */
class OnServerWorker extends \Server\Abstracts\Server
{


    /**
     * @var EventDispatch
     */
    #[Inject(EventDispatch::class)]
    public EventDispatch $eventDispatch;


    /**
     * @param Server $server
     * @param int $workerId
     * @throws Exception
     */
    public function onWorkerStart(Server $server, int $workerId)
    {
        $this->eventDispatch->dispatch(new OnBeforeWorkerStart($workerId));

        $this->eventDispatch->dispatch(new OnWorkerStart($server, $workerId));

        $this->eventDispatch->dispatch(new OnAfterWorkerStart());
    }


	/**
	 * @param OnBeforeWorkerStart $worker
	 * @throws Exception
	 */
    public function setConfigure(OnBeforeWorkerStart $worker)
    {
        ServerManager::setEnv('worker', $worker->workerId);
        $serialize = file_get_contents(storage(Runtime::CONFIG_NAME));
        if (!empty($serialize)) {
            Config::sets(unserialize($serialize));
        }
    }


    /**
     * @param Server $server
     * @param int $workerId
     * @throws Exception
     */
    public function onWorkerStop(Server $server, int $workerId)
    {
        $this->eventDispatch->dispatch(new OnWorkerStop($server, $workerId));

        Timer::clearAll();
    }


    /**
     * @param Server $server
     * @param int $workerId
     * @throws Exception
     */
    public function onWorkerExit(Server $server, int $workerId)
    {
        $this->eventDispatch->dispatch(new OnWorkerExit($server, $workerId));

        ServerManager::setEnv('state', 'exit');
    }


    /**
     * @param Server $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @param int $signal
     * @throws Exception
     */
    public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        $this->eventDispatch->dispatch(new OnWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal));

        $message = sprintf('Worker#%d::%d error stop. signal %d, exit_code %d, msg %s',
            $worker_id, $worker_pid, $signal, $exit_code, swoole_strerror(swoole_last_error(), 9)
        );

        $this->logger->error($message);

        $this->system_mail($message);
    }


    /**
     * @param $messageContent
     * @throws Exception
     */
    protected function system_mail($messageContent)
    {
        try {
            $email = Config::get('email');
            if (!empty($email) && ($email['enable'] ?? false) == true) {
                Help::sendEmail($email, 'Service Error', $messageContent);
            }
        } catch (\Throwable $e) {
            error($e, 'email');
        }
    }

}
