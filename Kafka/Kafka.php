<?php
declare(strict_types=1);

namespace Kafka;


use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\ConsumerTopic;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\TopicConf;
use Server\SInterface\CustomProcess;
use Snowflake\Abstracts\Config;
use Snowflake\Exception\ConfigException;
use Snowflake\Runtime;
use Snowflake\Snowflake;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\System;
use Swoole\Process;
use Throwable;

/**
 * Class Queue
 * @package Queue
 */
class Kafka implements CustomProcess
{

    protected Channel $channel;


    /**
     * Kafka constructor.
     * @param array $kafkaConfig
     */
    public function __construct(public array $kafkaConfig)
    {

    }


    /**
     * @return string
     * @throws ConfigException
     */
    public function getProcessName(Process $process): string
    {
        $name = Config::get('id', 'system') . '[' . $process->pid . ']';

        return $name . '.' . 'Kafka Consumer ' . $this->kafkaConfig['topic'];
    }


    /**
     * @param Process $process
     * @throws \Exception
     */
    public function onHandler(Process $process): void
    {
        $this->waite($process, $this->kafkaConfig);
    }


	/**
	 * @param Process $process
	 * @param array $kafkaServer
	 * @throws \Exception
	 */
    private function waite(Process $process, array $kafkaServer)
    {
        try {
            [$config, $topic, $conf] = $this->kafkaConfig($kafkaServer);
            if (empty($config) && empty($topic) && empty($conf)) {
                return;
            }
            $objRdKafka = new Consumer($config);
            $topic = $objRdKafka->newTopic($kafkaServer['topic'], $topic);

            $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
            do {
                $this->resolve($topic, $conf['interval'] ?? 1000);
            } while (true);
        } catch (Throwable $exception) {
            logger()->addError($exception, 'throwable');
        }
    }


    /**
     * @param ConsumerTopic $topic
     * @param $interval
     * @throws \Exception
     */
    private function resolve(ConsumerTopic $topic, $interval)
    {
        try {
            $message = $topic->consume(0, $interval);
            if (empty($message)) {
                return;
            }
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                $this->handlerExecute($message->topic_name, $message);
            } else if ($message->err == RD_KAFKA_RESP_ERR__PARTITION_EOF) {
                logger()->warning('No more messages; will wait for more');
            } else if ($message->err == RD_KAFKA_RESP_ERR__TIMED_OUT) {
                logger()->error('Kafka Timed out');
            } else {
                logger()->error($message->errstr());
            }
        } catch (Throwable $exception) {
            logger()->addError($exception, 'throwable');
        }
    }


    /**
     * @param $topic
     * @param $message
     * @throws \Exception
     */
    protected function handlerExecute($topic, $message)
    {
        go(function () use ($topic, $message) {
            try {
                $server = Snowflake::app()->getSwoole();

                $setting = $server->setting['worker_num'];

                /** @var KafkaProvider $container */
                $container = Snowflake::app()->get('kafka-container');
                $handler = $container->getConsumer($topic);

                if (empty($handler)) {
                    return;
                }

                $message = swoole_serialize(['action' => 'kafka', 'handler' => $handler, 'body' => [$topic, $message]]);

                $server->sendMessage($message, random_int(0, $setting - 1));
            } catch (Throwable $exception) {
                logger()->addError($exception, 'throwable');
            }
        });
    }


    /**
     * @param $kafka
     * @return array
     * @throws \Exception
     */
    private function kafkaConfig($kafka): array
    {
        try {
            $conf = new Conf();
            $conf->setRebalanceCb([$this, 'rebalanced_cb']);
            $conf->set('group.id', $kafka['groupId']);
            $conf->set('metadata.broker.list', $kafka['brokers']);
            $conf->set('socket.timeout.ms', '30000');

            debug('kafka listen groupId ' . $kafka['groupId']);
            debug('kafka listen brokers ' . $kafka['brokers']);

            if (function_exists('pcntl_sigprocmask')) {
                pcntl_sigprocmask(SIG_BLOCK, array(SIGIO));
                $conf->set('internal.termination.signal', (string)SIGIO);
            }

            $topicConf = new TopicConf();
            $topicConf->set('auto.commit.enable', '1');
            $topicConf->set('auto.commit.interval.ms', '100');

            //smallest：简单理解为从头开始消费，
            //largest：简单理解为从最新的开始消费
            $topicConf->set('auto.offset.reset', 'smallest');
            $topicConf->set('offset.store.path', 'kafka_offset.log');
            $topicConf->set('offset.store.method', 'broker');

            return [$conf, $topicConf, $kafka];
        } catch (Throwable $exception) {
            logger()->addError($exception, 'throwable');

            return [null, null, null];
        }

    }


    /**
     * @param KafkaConsumer $kafka
     * @param $err
     * @param array|null $partitions
     * @throws Exception
     * @throws \Exception
     */
    public function rebalanced_cb(KafkaConsumer $kafka, $err, array $partitions = null)
    {
        if ($err == RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
            $kafka->assign($partitions);
        } else if ($err == RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
            $kafka->assign(NULL);
        } else {
            throw new \Exception($err);
        }
    }


}
