<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kafka\Struct;
use Snowflake\Crontab\Crontab;
use Snowflake\Crontab\Producer;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPipeMessage
 * @package HttpServer\Events
 */
class OnPipeMessage extends Callback
{

    /**
     * @param Server $server
     * @param int $src_worker_id
     * @param $swollen_universalize
     * @throws Exception
     */
    public function onHandler(Server $server, int $src_worker_id, $swollen_universalize)
    {
        match ($swollen_universalize['action'] ?? null) {
            'kafka' => $this->onKafkaWorker($swollen_universalize),
            'crontab' => $this->onCrontabWorker($swollen_universalize),
            default => $this->onMessageWorker($server, $src_worker_id, $swollen_universalize)
        };
    }


    /**
     * @param array $message
     * @return string
     * @throws Exception
     */
    private function onCrontabWorker(array $message): string
    {
        if (empty($message['handler'] ?? null)) {
            throw new Exception('unknown handler');
        }
        /** @var Crontab $handler */
        $handler = swoole_unserialize($message['handler']);
        defer(function () use ($handler) {
            $return = $handler->isRecover();
            if ($return === 999) {
                $name = $handler->getName();

                $redis = Snowflake::app()->getRedis();
                if ($redis->exists('stop:crontab:' . $name)) {
                    $redis->del('crontab:' . $name);
                    $redis->del('stop:crontab:' . $name);
                } else {
                    $redis->set('crontab:' . $name, swoole_serialize($handler));
                    $tickTime = time() + $handler->getTickTime();
                    $redis->zAdd(Producer::CRONTAB_KEY, $tickTime, $name);
                }
            }
        });
        $handler->increment()->execute();
        return 'success';
    }


    /**
     * @param $server
     * @param $src_worker_id
     * @param $message
     * @return string
     * @throws Exception
     */
    private function onMessageWorker($server, $src_worker_id, $message): string
    {
        fire(Event::PIPE_MESSAGE, [$server, $src_worker_id, $message]);

        return 'success';
    }


    /**
     * @param array $message
     * @return string
     */
    private function onKafkaWorker(array $message): string
    {
        [$topic, $rdMessage] = $message['body'];

        call_user_func($message['handler'], new Struct($topic, $rdMessage));

        return 'success';
    }


}
