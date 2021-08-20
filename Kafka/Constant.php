<?php

namespace Kafka;

class Constant
{
	const CONFIG_BUILTIN_FEATURES = 'builtin.features';
	const CONFIG_CLIENT_ID = 'client.id';
	const CONFIG_METADATA_BROKER_LIST = 'metadata.broker.list';
	const CONFIG_BOOTSTRAP_SERVERS = 'bootstrap.servers';
	const CONFIG_MESSAGE_MAX_BYTES = 'message.max.bytes';
	const CONFIG_MESSAGE_COPY_MAX_BYTES = 'message.copy.max.bytes';
	const CONFIG_RECEIVE_MESSAGE_MAX_BYTES = 'receive.message.max.bytes';
	const CONFIG_MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION = 'max.in.flight.requests.per.connection';
	const CONFIG_MAX_IN_FLIGHT = 'max.in.flight';
	const CONFIG_TOPIC_METADATA_REFRESH_INTERVAL_MS = 'topic.metadata.refresh.interval.ms';
	const CONFIG_METADATA_MAX_AGE_MS = 'metadata.max.age.ms';
	const CONFIG_TOPIC_METADATA_REFRESH_FAST_INTERVAL_MS = 'topic.metadata.refresh.fast.interval.ms';
	const CONFIG_TOPIC_METADATA_REFRESH_FAST_CNT = 'topic.metadata.refresh.fast.cnt';
	const CONFIG_TOPIC_METADATA_REFRESH_SPARSE = 'topic.metadata.refresh.sparse';
	const CONFIG_TOPIC_METADATA_PROPAGATION_MAX_MS = 'topic.metadata.propagation.max.ms';
	const CONFIG_TOPIC_BLACKLIST = 'topic.blacklist';
	const CONFIG_DEBUG = 'debug';
	const CONFIG_SOCKET_TIMEOUT_MS = 'socket.timeout.ms';
	const CONFIG_SOCKET_BLOCKING_MAX_MS = 'socket.blocking.max.ms';
	const CONFIG_SOCKET_SEND_BUFFER_BYTES = 'socket.send.buffer.bytes';
	const CONFIG_SOCKET_RECEIVE_BUFFER_BYTES = 'socket.receive.buffer.bytes';
	const CONFIG_SOCKET_KEEPALIVE_ENABLE = 'socket.keepalive.enable';
	const CONFIG_SOCKET_NAGLE_DISABLE = 'socket.nagle.disable';
	const CONFIG_SOCKET_MAX_FAILS = 'socket.max.fails';
	const CONFIG_BROKER_ADDRESS_TTL = 'broker.address.ttl';
	const CONFIG_BROKER_ADDRESS_FAMILY = 'broker.address.family';
	const CONFIG_CONNECTIONS_MAX_IDLE_MS = 'connections.max.idle.ms';
	const CONFIG_RECONNECT_BACKOFF_JITTER_MS = 'reconnect.backoff.jitter.ms';
	const CONFIG_RECONNECT_BACKOFF_MS = 'reconnect.backoff.ms';
	const CONFIG_RECONNECT_BACKOFF_MAX_MS = 'reconnect.backoff.max.ms';
	const CONFIG_STATISTICS_INTERVAL_MS = 'statistics.interval.ms';
	const CONFIG_ENABLED_EVENTS = 'enabled_events';
	const CONFIG_ERROR_CB = 'error_cb';
	const CONFIG_THROTTLE_CB = 'throttle_cb';
	const CONFIG_STATS_CB = 'stats_cb';
	const CONFIG_LOG_CB = 'log_cb';
	const CONFIG_LOG_LEVEL = 'log_level';
	const CONFIG_LOG_QUEUE = 'log.queue';
	const CONFIG_LOG_THREAD_NAME = 'log.thread.name';
	const CONFIG_ENABLE_RANDOM_SEED = 'enable.random.seed';
	const CONFIG_LOG_CONNECTION_CLOSE = 'log.connection.close';
	const CONFIG_BACKGROUND_EVENT_CB = 'background_event_cb';
	const CONFIG_SOCKET_CB = 'socket_cb';
	const CONFIG_CONNECT_CB = 'connect_cb';
	const CONFIG_CLOSESOCKET_CB = 'closesocket_cb';
	const CONFIG_OPEN_CB = 'open_cb';
	const CONFIG_OPAQUE = 'opaque';
	const CONFIG_DEFAULT_TOPIC_CONF = 'default_topic_conf';
	const CONFIG_INTERNAL_TERMINATION_SIGNAL = 'internal.termination.signal';
	const CONFIG_API_VERSION_REQUEST = 'api.version.request';
	const CONFIG_API_VERSION_REQUEST_TIMEOUT_MS = 'api.version.request.timeout.ms';
	const CONFIG_API_VERSION_FALLBACK_MS = 'api.version.fallback.ms';
	const CONFIG_BROKER_VERSION_FALLBACK = 'broker.version.fallback';
	const CONFIG_SECURITY_PROTOCOL = 'security.protocol';
	const CONFIG_SSL_CIPHER_SUITES = 'ssl.cipher.suites';
	const CONFIG_SSL_CURVES_LIST = 'ssl.curves.list';
	const CONFIG_SSL_SIGALGS_LIST = 'ssl.sigalgs.list';
	const CONFIG_SSL_KEY_LOCATION = 'ssl.key.location';
	const CONFIG_SSL_KEY_PASSWORD = 'ssl.key.password';
	const CONFIG_SSL_KEY_PEM = 'ssl.key.pem';
	const CONFIG_SSL_KEY = 'ssl_key';
	const CONFIG_SSL_CERTIFICATE_LOCATION = 'ssl.certificate.location';
	const CONFIG_SSL_CERTIFICATE_PEM = 'ssl.certificate.pem';
	const CONFIG_SSL_CERTIFICATE = 'ssl_certificate';
	const CONFIG_SSL_CA_LOCATION = 'ssl.ca.location';
	const CONFIG_SSL_CA = 'ssl_ca';
	const CONFIG_SSL_CA_CERTIFICATE_STORES = 'ssl.ca.certificate.stores';
	const CONFIG_SSL_CRL_LOCATION = 'ssl.crl.location';
	const CONFIG_SSL_KEYSTORE_LOCATION = 'ssl.keystore.location';
	const CONFIG_SSL_KEYSTORE_PASSWORD = 'ssl.keystore.password';
	const CONFIG_SSL_ENGINE_LOCATION = 'ssl.engine.location';
	const CONFIG_SSL_ENGINE_ID = 'ssl.engine.id';
	const CONFIG_SSL_ENGINE_CALLBACK_DATA = 'ssl_engine_callback_data';
	const CONFIG_ENABLE_SSL_CERTIFICATE_VERIFICATION = 'enable.ssl.certificate.verification';
	const CONFIG_SSL_ENDPOINT_IDENTIFICATION_ALGORITHM = 'ssl.endpoint.identification.algorithm';
	const CONFIG_SSL_CERTIFICATE_VERIFY_CB = 'ssl.certificate.verify_cb';
	const CONFIG_SASL_MECHANISMS = 'sasl.mechanisms';
	const CONFIG_SASL_MECHANISM = 'sasl.mechanism';
	const CONFIG_SASL_KERBEROS_SERVICE_NAME = 'sasl.kerberos.service.name';
	const CONFIG_SASL_KERBEROS_PRINCIPAL = 'sasl.kerberos.principal';
	const CONFIG_SASL_KERBEROS_KINIT_CMD = 'sasl.kerberos.kinit.cmd';
	const CONFIG_SASL_KERBEROS_KEYTAB = 'sasl.kerberos.keytab';
	const CONFIG_SASL_KERBEROS_MIN_TIME_BEFORE_RELOGIN = 'sasl.kerberos.min.time.before.relogin';
	const CONFIG_SASL_USERNAME = 'sasl.username';
	const CONFIG_SASL_PASSWORD = 'sasl.password';
	const CONFIG_SASL_OAUTHBEARER_CONFIG = 'sasl.oauthbearer.config';
	const CONFIG_ENABLE_SASL_OAUTHBEARER_UNSECURE_JWT = 'enable.sasl.oauthbearer.unsecure.jwt';
	const CONFIG_OAUTHBEARER_TOKEN_REFRESH_CB = 'oauthbearer_token_refresh_cb';
	const CONFIG_PLUGIN_LIBRARY_PATHS = 'plugin.library.paths';
	const CONFIG_INTERCEPTORS = 'interceptors';
	const CONFIG_GROUP_ID = 'group.id';
	const CONFIG_GROUP_INSTANCE_ID = 'group.instance.id';
	const CONFIG_PARTITION_ASSIGNMENT_STRATEGY = 'partition.assignment.strategy';
	const CONFIG_SESSION_TIMEOUT_MS = 'session.timeout.ms';
	const CONFIG_HEARTBEAT_INTERVAL_MS = 'heartbeat.interval.ms';
	const CONFIG_GROUP_PROTOCOL_TYPE = 'group.protocol.type';
	const CONFIG_COORDINATOR_QUERY_INTERVAL_MS = 'coordinator.query.interval.ms';
	const CONFIG_MAX_POLL_INTERVAL_MS = 'max.poll.interval.ms';
	const CONFIG_ENABLE_AUTO_COMMIT = 'enable.auto.commit';
	const CONFIG_AUTO_COMMIT_INTERVAL_MS = 'auto.commit.interval.ms';
	const CONFIG_ENABLE_AUTO_OFFSET_STORE = 'enable.auto.offset.store';
	const CONFIG_QUEUED_MIN_MESSAGES = 'queued.min.messages';
	const CONFIG_QUEUED_MAX_MESSAGES_KBYTES = 'queued.max.messages.kbytes';
	const CONFIG_FETCH_WAIT_MAX_MS = 'fetch.wait.max.ms';
	const CONFIG_FETCH_MESSAGE_MAX_BYTES = 'fetch.message.max.bytes';
	const CONFIG_MAX_PARTITION_FETCH_BYTES = 'max.partition.fetch.bytes';
	const CONFIG_FETCH_MAX_BYTES = 'fetch.max.bytes';
	const CONFIG_FETCH_MIN_BYTES = 'fetch.min.bytes';
	const CONFIG_FETCH_ERROR_BACKOFF_MS = 'fetch.error.backoff.ms';
	const CONFIG_OFFSET_STORE_METHOD = 'offset.store.method';
	const CONFIG_ISOLATION_LEVEL = 'isolation.level';
	const CONFIG_CONSUME_CB = 'consume_cb';
	const CONFIG_REBALANCE_CB = 'rebalance_cb';
	const CONFIG_OFFSET_COMMIT_CB = 'offset_commit_cb';
	const CONFIG_ENABLE_PARTITION_EOF = 'enable.partition.eof';
	const CONFIG_CHECK_CRCS = 'check.crcs';
	const CONFIG_ALLOW_AUTO_CREATE_TOPICS = 'allow.auto.create.topics';
	const CONFIG_CLIENT_RACK = 'client.rack';
	const CONFIG_TRANSACTIONAL_ID = 'transactional.id';
	const CONFIG_TRANSACTION_TIMEOUT_MS = 'transaction.timeout.ms';
	const CONFIG_ENABLE_IDEMPOTENCE = 'enable.idempotence';
	const CONFIG_ENABLE_GAPLESS_GUARANTEE = 'enable.gapless.guarantee';
	const CONFIG_QUEUE_BUFFERING_MAX_MESSAGES = 'queue.buffering.max.messages';
	const CONFIG_QUEUE_BUFFERING_MAX_KBYTES = 'queue.buffering.max.kbytes';
	const CONFIG_QUEUE_BUFFERING_MAX_MS = 'queue.buffering.max.ms';
	const CONFIG_LINGER_MS = 'linger.ms';
	const CONFIG_MESSAGE_SEND_MAX_RETRIES = 'message.send.max.retries';
	const CONFIG_RETRIES = 'retries';
	const CONFIG_RETRY_BACKOFF_MS = 'retry.backoff.ms';
	const CONFIG_QUEUE_BUFFERING_BACKPRESSURE_THRESHOLD = 'queue.buffering.backpressure.threshold';
	const CONFIG_COMPRESSION_CODEC = 'compression.codec';
	const CONFIG_COMPRESSION_TYPE = 'compression.type';
	const CONFIG_BATCH_NUM_MESSAGES = 'batch.num.messages';
	const CONFIG_BATCH_SIZE = 'batch.size';
	const CONFIG_DELIVERY_REPORT_ONLY_ERROR = 'delivery.report.only.error';
	const CONFIG_DR_CB = 'dr_cb';
	const CONFIG_DR_MSG_CB = 'dr_msg_cb';
	const CONFIG_STICKY_PARTITIONING_LINGER_MS = 'sticky.partitioning.linger.ms';


	const TOPIC_CONF_REQUEST_REQUIRED_ACKS = 'request.required.acks';
	const TOPIC_CONF_ACKS = 'acks';
	const TOPIC_CONF_REQUEST_TIMEOUT_MS = 'request.timeout.ms';
	const TOPIC_CONF_MESSAGE_TIMEOUT_MS = 'message.timeout.ms';
	const TOPIC_CONF_DELIVERY_TIMEOUT_MS = 'delivery.timeout.ms';
	const TOPIC_CONF_QUEUING_STRATEGY = 'queuing.strategy';
	const TOPIC_CONF_PRODUCE_OFFSET_REPORT = 'produce.offset.report';
	const TOPIC_CONF_PARTITIONER = 'partitioner';
	const TOPIC_CONF_PARTITIONER_CB = 'partitioner_cb';
	const TOPIC_CONF_MSG_ORDER_CMP = 'msg_order_cmp';
	const TOPIC_CONF_OPAQUE = 'opaque';
	const TOPIC_CONF_COMPRESSION_CODEC = 'compression.codec';
	const TOPIC_CONF_COMPRESSION_TYPE = 'compression.type';
	const TOPIC_CONF_COMPRESSION_LEVEL = 'compression.level';
	const TOPIC_CONF_AUTO_COMMIT_ENABLE = 'auto.commit.enable';
	const TOPIC_CONF_ENABLE_AUTO_COMMIT = 'enable.auto.commit';
	const TOPIC_CONF_AUTO_COMMIT_INTERVAL_MS = 'auto.commit.interval.ms';
	const TOPIC_CONF_AUTO_OFFSET_RESET = 'auto.offset.reset';
	const TOPIC_CONF_OFFSET_STORE_PATH = 'offset.store.path';
	const TOPIC_CONF_OFFSET_STORE_SYNC_INTERVAL_MS = 'offset.store.sync.interval.ms';
	const TOPIC_CONF_OFFSET_STORE_METHOD = 'offset.store.method';
	const TOPIC_CONF_CONSUME_CALLBACK_MAX_MESSAGES = 'consume.callback.max.messages';

}
