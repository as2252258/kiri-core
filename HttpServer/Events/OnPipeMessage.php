<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Loader;
use Exception;
use HttpServer\Abstracts\Callback;
use Kafka\ConsumerInterface;
use Kafka\Struct;
use Kafka\TaskContainer;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
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
     * @param $message
     * @throws ComponentException
     * @throws Exception
     */
    public function onHandler(Server $server, int $src_worker_id, $message)
    {
        try {
            $swoole_unserialize = swoole_unserialize($message);
            match ($swoole_unserialize['action'] ?? null) {
                'kafka' => $this->onKafkaWorker($swoole_unserialize),
                default => $this->onMessageWorker($server, $src_worker_id, $message)
            };
        } catch (\Throwable $exception) {
            $this->addError($exception);
        } finally {
            fire(Event::SYSTEM_RESOURCE_RELEASES);
        }
    }


    /**
     * @param $server
     * @param $src_worker_id
     * @param $message
     * @throws \Exception
     */
    private function onMessageWorker($server, $src_worker_id, $message)
    {
        fire(Event::PIPE_MESSAGE, [$server, $src_worker_id, $message]);

        return 'success';
    }


    /**
     * @param array $message
     * @throws \ReflectionException
     * @throws \Snowflake\Exception\NotFindClassException
     */
    private function onKafkaWorker(array $message)
    {
        [$topic, $message] = $message['body'];

        /** @var TaskContainer $container */
        $container = Snowflake::app()->get('kafka-container');
        $container->process($topic, new Struct($topic, $message));
        return 'success';
    }


}
