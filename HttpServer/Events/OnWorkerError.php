<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerError
 * @package HttpServer\Events
 */
class OnWorkerError extends Callback
{


    /**
     * @param Server $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @param int $signal
     * @throws Exception
     */
    public function onHandler(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        Event::trigger(Event::SERVER_WORKER_ERROR);

        $message = sprintf('Worker#%d::%d error stop. signal %d, exit_code %d, msg %s',
            $worker_id, $worker_pid, $signal, $exit_code, swoole_strerror(swoole_last_error(), 9)
        );
        write($message, 'worker-exit');


        $email = Config::get('email');
        if (empty($email) || !$email['enable']) {
            return;
        }
        $transport = (new \Swift_SmtpTransport($email['host'], $email['465']))
            ->setUsername($email['username'])
            ->setPassword($email['password']);
        $mailer = new \Swift_Mailer($transport);

        // Create a message
        $message = (new \Swift_Message('Wonderful Subject'))
            ->setFrom([$email['send']['address'] => $email['send']['nickname']])
            ->setBody('Here is the message itself');

        foreach ($email['receive'] as $item) {
            $message->setTo([$item['address'], $item['address'] => $item['nickname']]);
        }
        $mailer->send($message);
    }

}
