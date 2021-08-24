<?php

namespace Kafka;

use RdKafka\TopicConf;


/**
 *
 */
class TopicConfig extends TopicConf
{

	/**
	 * @param mixed|string $request_required_acks
	 *
	 * 此配置是表明当一次produce请求被认为完成时的确认值。特别是，多少个其他brokers必须已经提交了数据到他们的log并且向他们的leader确认了这些信息。典型的值包括：
	 * 0： 表示producer从来不等待来自broker的确认信息（和0.7一样的行为）。这个选择提供了最小的时延但同时风险最大（因为当server宕机时，数据将会丢失）。
	 * 1：表示获得leader replica已经接收了数据的确认信息。这个选择时延较小同时确保了server确认接收成功。
	 * -1：producer会获得所有同步replicas都收到数据的确认。同时时延最大，然而，这种方式并没有完全消除丢失消息的风险，因为同步replicas的数量可能是1.如果你想确保某些replicas接收到数据，那么你应该在topic-level设置中选项min.insync.replicas设置一下。请阅读一下设计文档，可以获得更深入的讨论。
	 */
	public function setRequestRequiredAcks(int $request_required_acks): void
	{
		$this->set(Constant::TOPIC_CONF_REQUEST_REQUIRED_ACKS, $request_required_acks);
	}


	/**
	 * @param mixed|string $acks
	 *
	 * producer需要server接收到数据之后发出的确认接收的信号，此项配置就是指procuder需要多少个这样的确认信号。此配置实际上代表了数据备份的可用性。以下设置为常用选项：
	 * （1）acks=0： 设置为0表示producer不需要等待任何确认收到的信息。副本将立即加到socket  buffer并认为已经发送。没有任何保障可以保证此种情况下server已经成功接收数据，同时重试配置不会发生作用（因为客户端不知道是否失败）回馈的offset会总是设置为-1；
	 * （2）acks=1： 这意味着至少要等待leader已经成功将数据写入本地log，但是并没有等待所有follower是否成功写入。这种情况下，如果follower没有成功备份数据，而此时leader又挂掉，则消息会丢失。
	 * （3）acks=all： 这意味着leader需要等待所有备份都成功写入日志，这种策略会保证只要有一个备份存活就不会丢失数据。这是最强的保证。
	 * （4）其他的设置，例如acks=2也是可以的，这将需要给定的acks数量，但是这种策略一般很少用。
	 */
	public function setAcks(int $acks): void
	{
		$this->set(Constant::TOPIC_CONF_ACKS, $acks);
	}

	/**
	 * @param mixed|string $request_timeout_ms
	 *
	 * broker尽力实现request.required.acks需求时的等待时间，否则会发送错误到客户端
	 */
	public function setRequestTimeoutMs(int $request_timeout_ms): void
	{
		$this->set(Constant::TOPIC_CONF_REQUEST_TIMEOUT_MS, $request_timeout_ms);
	}

	/**
	 * @param mixed|string $message_timeout_ms
	 *
	 * 本地消息超时。此值仅在本地强制执行，并限制生成的消息等待成功传递的时间。0的时间是无限的。
	 * 这是librdkafka用于传递消息（包括重试）的最长时间。
	 * 超过重试计数或消息超时时发生传递错误。
	 * 如果配置了transactional.id，则消息超时将自动调整为transaction.timeout.ms。
	 */
	public function setMessageTimeoutMs(int $message_timeout_ms): void
	{
		$this->set(Constant::TOPIC_CONF_MESSAGE_TIMEOUT_MS, $message_timeout_ms);
	}

	/**
	 * @param mixed|string $delivery_timeout_ms
	 *
	 * Alias for message.timeout.ms: Local message timeout.
	 * This value is only enforced locally and limits the time a produced message waits for successful delivery.
	 * A time of 0 is infinite.
	 * This is the maximum time librdkafka may use to deliver a message (including retries).
	 * Delivery error occurs when either the retry count or the message timeout are exceeded.
	 * The message timeout is automatically adjusted to transaction.timeout.ms if transactional.id is configured.
	 */
	public function setDeliveryTimeoutMs(int $delivery_timeout_ms): void
	{
		$this->set(Constant::TOPIC_CONF_DELIVERY_TIMEOUT_MS, $delivery_timeout_ms);
	}

	/**
	 * @param mixed|string $queuing_strategy
	 *
	 * EXPERIMENTAL: subject to change or removal.
	 * DEPRECATED Producer queuing strategy.
	 * FIFO preserves produce ordering, while LIFO prioritizes new messages
	 */
	public function setQueuingStrategy(mixed $queuing_strategy): void
	{
		$this->set(Constant::TOPIC_CONF_QUEUING_STRATEGY, $queuing_strategy);
	}

	/**
	 * @param mixed|string $produce_offset_report
	 *
	 * DEPRECATED No longer used.
	 */
	public function setProduceOffsetReport(bool $produce_offset_report): void
	{
		$this->set(Constant::TOPIC_CONF_PRODUCE_OFFSET_REPORT, $produce_offset_report);
	}

	/**
	 * @param mixed|string $partitioner
	 *
	 * Partitioner: random - random distribution,
	 * consistent - CRC32 hash of key (Empty and NULL keys are mapped to single partition),
	 * consistent_random - CRC32 hash of key (Empty and NULL keys are randomly partitioned),
	 * murmur2 - Java Producer compatible Murmur2 hash of key (NULL keys are mapped to single partition),
	 * murmur2_random - Java Producer compatible Murmur2 hash of key (NULL keys are randomly partitioned.
	 * This is functionally equivalent to the default partitioner in the Java Producer.),
	 * fnv1a - FNV-1a hash of key (NULL keys are mapped to single partition),
	 * fnv1a_random - FNV-1a hash of key (NULL keys are randomly partitioned).
	 */
	public function setPartitioner(mixed $partitioner): void
	{
		$this->set(Constant::TOPIC_CONF_PARTITIONER, $partitioner);
	}

	/**
	 * @param mixed|string $partitioner_cb
	 *
	 * Custom partitioner callback (set with rd_kafka_topic_conf_set_partitioner_cb())
	 */
	public function setPartitionerCb(mixed $partitioner_cb): void
	{
		$this->set(Constant::TOPIC_CONF_PARTITIONER_CB, $partitioner_cb);
	}

	/**
	 * @param mixed|string $msg_order_cmp
	 *
	 * EXPERIMENTAL: subject to change or removal.
	 * DEPRECATED Message queue ordering comparator (set with rd_kafka_topic_conf_set_msg_order_cmp()).
	 * Also see queuing.strategy.
	 *
	 */
	public function setMsgOrderCmp(mixed $msg_order_cmp): void
	{
		$this->set(Constant::TOPIC_CONF_MSG_ORDER_CMP, $msg_order_cmp);
	}

	/**
	 * @param mixed|string $opaque
	 *
	 * Application opaque (set with rd_kafka_topic_conf_set_opaque())
	 */
	public function setOpaque(mixed $opaque): void
	{
		$this->set(Constant::TOPIC_CONF_OPAQUE, $opaque);
	}

	/**
	 * @param mixed|string $compression_codec
	 *
	 * Compression codec to use for compressing message sets. inherit = inherit global compression.codec configuration.
	 */
	public function setCompressionCodec(mixed $compression_codec): void
	{
		$this->set(Constant::TOPIC_CONF_COMPRESSION_CODEC, $compression_codec);
	}

	/**
	 * @param mixed|string $compression_type
	 *
	 * Alias for compression.codec: compression codec to use for compressing message sets.
	 * This is the default value for all topics, may be overridden by the topic configuration property compression.codec.
	 */
	public function setCompressionType(mixed $compression_type): void
	{
		$this->set(Constant::TOPIC_CONF_COMPRESSION_TYPE, $compression_type);
	}

	/**
	 * @param mixed|string $compression_level
	 *
	 * Compression level parameter for algorithm selected by configuration property compression.codec.
	 * Higher values will result in better compression at the cost of more CPU usage.
	 * Usable range is algorithm-dependent: [0-9] for gzip; [0-12] for lz4; only 0 for snappy;
	 * -1 = codec-dependent default compression level.
	 */
	public function setCompressionLevel(int $compression_level): void
	{
		$this->set(Constant::TOPIC_CONF_COMPRESSION_LEVEL, $compression_level);
	}

	/**
	 * @param mixed|string $auto_commit_enable
	 *
	 * DEPRECATED [LEGACY PROPERTY: This property is used by the simple legacy consumer only.
	 *      When using the high-level KafkaConsumer, the global enable.auto.commit property must be used instead].
	 * If true, periodically commit offset of the last message handed to the application.
	 *      This committed offset will be used when the process restarts to pick up where it left off.
	 * If false, the application will have to call rd_kafka_offset_store() to store an offset (optional).
	 *      Offsets will be written to broker or local file according to offset.store.method.
	 */
	public function setAutoCommitEnable(bool $auto_commit_enable): void
	{
		$this->set(Constant::TOPIC_CONF_AUTO_COMMIT_ENABLE, $auto_commit_enable);
	}

	/**
	 * @param mixed|string $enable_auto_commit
	 *
	 * DEPRECATED Alias for auto.commit.enable: [LEGACY PROPERTY: This property is used by the simple legacy consumer only.
	 * When using the high-level KafkaConsumer, the global enable.auto.commit property must be used instead].
	 * If true, periodically commit offset of the last message handed to the application.
	 *      This committed offset will be used when the process restarts to pick up where it left off.
	 * If false, the application will have to call rd_kafka_offset_store() to store an offset (optional).
	 *      Offsets will be written to broker or local file according to offset.store.method.
	 */
	public function setEnableAutoCommit(bool $enable_auto_commit): void
	{
		$this->set(Constant::TOPIC_CONF_ENABLE_AUTO_COMMIT, $enable_auto_commit);
	}

	/**
	 * @param mixed|string $auto_commit_interval_ms
	 *
	 * [LEGACY PROPERTY: This setting is used by the simple legacy consumer only.
	 *      When using the high-level KafkaConsumer, the global auto.commit.interval.ms property must be used instead].
	 *      The frequency in milliseconds that the consumer offsets are committed (written) to offset storage.
	 */
	public function setAutoCommitIntervalMs(int $auto_commit_interval_ms): void
	{
		$this->set(Constant::TOPIC_CONF_AUTO_COMMIT_INTERVAL_MS, $auto_commit_interval_ms);
	}

	/**
	 * @param mixed|string $auto_offset_reset
	 *
	 * Action to take when there is no initial offset in offset store or the desired offset is out of range:
	 * 'smallest','earliest' - automatically reset the offset to the smallest offset,
	 * 'largest','latest' - automatically reset the offset to the largest offset,
	 * 'error' - trigger an error (ERR__AUTO_OFFSET_RESET) which is retrieved by consuming messages and checking 'message->err'.
	 */
	public function setAutoOffsetReset(mixed $auto_offset_reset): void
	{
		$this->set(Constant::TOPIC_CONF_AUTO_OFFSET_RESET, $auto_offset_reset);
	}

	/**
	 * @param mixed|string $offset_store_path
	 *
	 * DEPRECATED Path to local file for storing offsets.
	 * If the path is a directory a filename will be automatically generated in that directory based on the topic and partition.
	 * File-based offset storage will be removed in a future version.
	 */
	public function setOffsetStorePath(string $offset_store_path): void
	{
		$this->set(Constant::TOPIC_CONF_OFFSET_STORE_PATH, $offset_store_path);
	}

	/**
	 * @param mixed|string $offset_store_sync_interval_ms
	 *
	 * DEPRECATED fsync() interval for the offset file, in milliseconds.
	 * Use -1 to disable syncing, and 0 for immediate sync after each write.
	 * File-based offset storage will be removed in a future version.
	 */
	public function setOffsetStoreSyncIntervalMs(int $offset_store_sync_interval_ms): void
	{
		$this->set(Constant::TOPIC_CONF_OFFSET_STORE_SYNC_INTERVAL_MS, $offset_store_sync_interval_ms);
	}

	/**
	 * @param mixed|string $offset_store_method
	 *
	 * DEPRECATED Offset commit store method:
	 *  'file' - DEPRECATED: local file store (offset.store.path, et.al),
	 *  'broker' - broker commit store (requires "group.id" to be configured and Apache Kafka 0.8.2 or later on the broker.).
	 */
	public function setOffsetStoreMethod(mixed $offset_store_method): void
	{
		$this->set(Constant::TOPIC_CONF_OFFSET_STORE_METHOD, $offset_store_method);
	}

	/**
	 * @param mixed|string $consume_callback_max_messages
	 *
	 * Maximum number of messages to dispatch in one rd_kafka_consume_callback*()
	 */
	public function setConsumeCallbackMaxMessages(int $consume_callback_max_messages): void
	{
		$this->set(Constant::TOPIC_CONF_CONSUME_CALLBACK_MAX_MESSAGES, $consume_callback_max_messages);
	}

}
