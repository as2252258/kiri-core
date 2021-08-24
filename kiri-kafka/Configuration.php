<?php

namespace Kafka;

use RdKafka\Conf;


/**
 *
 */
class Configuration extends Conf
{


	/**
	 * @param mixed $builtin_features
	 */
	public function setBuiltinFeatures(mixed $builtin_features): void
	{
		$this->set(Constant::CONFIG_BUILTIN_FEATURES, $builtin_features);
	}

	/**
	 * @param mixed $client_id
	 */
	public function setClientId(mixed $client_id): void
	{
		$this->set(Constant::CONFIG_CLIENT_ID, $client_id);
	}

	/**
	 * @param mixed $metadata_broker_list
	 */
	public function setMetadataBrokerList(mixed $metadata_broker_list): void
	{
		$this->set(Constant::CONFIG_METADATA_BROKER_LIST, $metadata_broker_list);
	}

	/**
	 * @param mixed $bootstrap_servers
	 */
	public function setBootstrapServers(mixed $bootstrap_servers): void
	{
		$this->set(Constant::CONFIG_BOOTSTRAP_SERVERS, $bootstrap_servers);
	}

	/**
	 * @param mixed $message_max_bytes
	 */
	public function setMessageMaxBytes(mixed $message_max_bytes): void
	{
		$this->set(Constant::CONFIG_MESSAGE_MAX_BYTES, $message_max_bytes);
	}

	/**
	 * @param mixed $message_copy_max_bytes
	 */
	public function setMessageCopyMaxBytes(mixed $message_copy_max_bytes): void
	{
		$this->set(Constant::CONFIG_MESSAGE_COPY_MAX_BYTES, $message_copy_max_bytes);
	}

	/**
	 * @param mixed $receive_message_max_bytes
	 */
	public function setReceiveMessageMaxBytes(mixed $receive_message_max_bytes): void
	{
		$this->set(Constant::CONFIG_RECEIVE_MESSAGE_MAX_BYTES, $receive_message_max_bytes);
	}

	/**
	 * @param mixed $max_in_flight_requests_per_connection
	 */
	public function setMaxInFlightRequestsPerConnection(mixed $max_in_flight_requests_per_connection): void
	{
		$this->set(Constant::CONFIG_MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION, $max_in_flight_requests_per_connection);
	}

	/**
	 * @param mixed $max_in_flight
	 */
	public function setMaxInFlight(mixed $max_in_flight): void
	{
		$this->set(Constant::CONFIG_MAX_IN_FLIGHT, $max_in_flight);
	}

	/**
	 * @param mixed $topic_metadata_refresh_interval_ms
	 */
	public function setTopicMetadataRefreshIntervalMs(mixed $topic_metadata_refresh_interval_ms): void
	{
		$this->set(Constant::CONFIG_TOPIC_METADATA_REFRESH_INTERVAL_MS, $topic_metadata_refresh_interval_ms);
	}

	/**
	 * @param mixed $metadata_max_age_ms
	 */
	public function setMetadataMaxAgeMs(mixed $metadata_max_age_ms): void
	{
		$this->set(Constant::CONFIG_METADATA_MAX_AGE_MS, $metadata_max_age_ms);
	}

	/**
	 * @param mixed $topic_metadata_refresh_fast_interval_ms
	 */
	public function setTopicMetadataRefreshFastIntervalMs(mixed $topic_metadata_refresh_fast_interval_ms): void
	{
		$this->set(Constant::CONFIG_TOPIC_METADATA_REFRESH_FAST_INTERVAL_MS, $topic_metadata_refresh_fast_interval_ms);
	}

	/**
	 * @param mixed $topic_metadata_refresh_fast_cnt
	 */
	public function setTopicMetadataRefreshFastCnt(mixed $topic_metadata_refresh_fast_cnt): void
	{
		$this->set(Constant::CONFIG_TOPIC_METADATA_REFRESH_FAST_CNT, $topic_metadata_refresh_fast_cnt);
	}

	/**
	 * @param mixed $topic_metadata_refresh_sparse
	 */
	public function setTopicMetadataRefreshSparse(mixed $topic_metadata_refresh_sparse): void
	{
		$this->set(Constant::CONFIG_TOPIC_METADATA_REFRESH_SPARSE, $topic_metadata_refresh_sparse);
	}

	/**
	 * @param mixed $topic_metadata_propagation_max_ms
	 */
	public function setTopicMetadataPropagationMaxMs(mixed $topic_metadata_propagation_max_ms): void
	{
		$this->set(Constant::CONFIG_TOPIC_METADATA_PROPAGATION_MAX_MS, $topic_metadata_propagation_max_ms);
	}

	/**
	 * @param mixed $topic_blacklist
	 */
	public function setTopicBlacklist(mixed $topic_blacklist): void
	{
		$this->set(Constant::CONFIG_TOPIC_BLACKLIST, $topic_blacklist);
	}

	/**
	 * @param mixed $debug
	 */
	public function setDebug(mixed $debug): void
	{
		$this->set(Constant::CONFIG_DEBUG, $debug);
	}

	/**
	 * @param mixed $socket_timeout_ms
	 */
	public function setSocketTimeoutMs(mixed $socket_timeout_ms): void
	{
		$this->set(Constant::CONFIG_SOCKET_TIMEOUT_MS, $socket_timeout_ms);
	}

	/**
	 * @param mixed $socket_blocking_max_ms
	 */
	public function setSocketBlockingMaxMs(mixed $socket_blocking_max_ms): void
	{
		$this->set(Constant::CONFIG_SOCKET_BLOCKING_MAX_MS, $socket_blocking_max_ms);
	}

	/**
	 * @param mixed $socket_send_buffer_bytes
	 */
	public function setSocketSendBufferBytes(mixed $socket_send_buffer_bytes): void
	{
		$this->set(Constant::CONFIG_SOCKET_SEND_BUFFER_BYTES, $socket_send_buffer_bytes);
	}

	/**
	 * @param mixed $socket_receive_buffer_bytes
	 */
	public function setSocketReceiveBufferBytes(mixed $socket_receive_buffer_bytes): void
	{
		$this->set(Constant::CONFIG_SOCKET_RECEIVE_BUFFER_BYTES, $socket_receive_buffer_bytes);
	}

	/**
	 * @param mixed $socket_keepalive_enable
	 */
	public function setSocketKeepaliveEnable(mixed $socket_keepalive_enable): void
	{
		$this->set(Constant::CONFIG_SOCKET_KEEPALIVE_ENABLE, $socket_keepalive_enable);
	}

	/**
	 * @param mixed $socket_nagle_disable
	 */
	public function setSocketNagleDisable(mixed $socket_nagle_disable): void
	{
		$this->set(Constant::CONFIG_SOCKET_NAGLE_DISABLE, $socket_nagle_disable);
	}

	/**
	 * @param mixed $socket_max_fails
	 */
	public function setSocketMaxFails(mixed $socket_max_fails): void
	{
		$this->set(Constant::CONFIG_SOCKET_MAX_FAILS, $socket_max_fails);
	}

	/**
	 * @param mixed $broker_address_ttl
	 */
	public function setBrokerAddressTtl(mixed $broker_address_ttl): void
	{
		$this->set(Constant::CONFIG_BROKER_ADDRESS_TTL, $broker_address_ttl);
	}

	/**
	 * @param mixed $broker_address_family
	 */
	public function setBrokerAddressFamily(mixed $broker_address_family): void
	{
		$this->set(Constant::CONFIG_BROKER_ADDRESS_FAMILY, $broker_address_family);
	}

	/**
	 * @param mixed $connections_max_idle_ms
	 */
	public function setConnectionsMaxIdleMs(mixed $connections_max_idle_ms): void
	{
		$this->set(Constant::CONFIG_CONNECTIONS_MAX_IDLE_MS, $connections_max_idle_ms);
	}

	/**
	 * @param mixed $reconnect_backoff_jitter_ms
	 */
	public function setReconnectBackoffJitterMs(mixed $reconnect_backoff_jitter_ms): void
	{
		$this->set(Constant::CONFIG_RECONNECT_BACKOFF_JITTER_MS, $reconnect_backoff_jitter_ms);
	}

	/**
	 * @param mixed $reconnect_backoff_ms
	 */
	public function setReconnectBackoffMs(mixed $reconnect_backoff_ms): void
	{
		$this->set(Constant::CONFIG_RECONNECT_BACKOFF_MS, $reconnect_backoff_ms);
	}

	/**
	 * @param mixed $reconnect_backoff_max_ms
	 */
	public function setReconnectBackoffMaxMs(mixed $reconnect_backoff_max_ms): void
	{
		$this->set(Constant::CONFIG_RECONNECT_BACKOFF_MAX_MS, $reconnect_backoff_max_ms);
	}

	/**
	 * @param mixed $statistics_interval_ms
	 */
	public function setStatisticsIntervalMs(mixed $statistics_interval_ms): void
	{
		$this->set(Constant::CONFIG_STATISTICS_INTERVAL_MS, $statistics_interval_ms);
	}

	/**
	 * @param mixed $enabled_events
	 */
	public function setEnabledEvents(mixed $enabled_events): void
	{
		$this->set(Constant::CONFIG_ENABLED_EVENTS, $enabled_events);
	}

	/**
	 * @param mixed $throttle_cb
	 */
	public function setThrottleCb(mixed $throttle_cb): void
	{
		$this->set(Constant::CONFIG_THROTTLE_CB, $throttle_cb);
	}


	/**
	 * @param mixed $log_level
	 */
	public function setLogLevel(mixed $log_level): void
	{
		$this->set(Constant::CONFIG_LOG_LEVEL, $log_level);
	}

	/**
	 * @param mixed $log_queue
	 */
	public function setLogQueue(mixed $log_queue): void
	{
		$this->set(Constant::CONFIG_LOG_QUEUE, $log_queue);
	}

	/**
	 * @param mixed $log_thread_name
	 */
	public function setLogThreadName(mixed $log_thread_name): void
	{
		$this->set(Constant::CONFIG_LOG_THREAD_NAME, $log_thread_name);
	}

	/**
	 * @param mixed $enable_random_seed
	 */
	public function setEnableRandomSeed(mixed $enable_random_seed): void
	{
		$this->set(Constant::CONFIG_ENABLE_RANDOM_SEED, $enable_random_seed);
	}

	/**
	 * @param mixed $log_connection_close
	 */
	public function setLogConnectionClose(mixed $log_connection_close): void
	{
		$this->set(Constant::CONFIG_LOG_CONNECTION_CLOSE, $log_connection_close);
	}

	/**
	 * @param mixed $background_event_cb
	 */
	public function setBackgroundEventCb(mixed $background_event_cb): void
	{
		$this->set(Constant::CONFIG_BACKGROUND_EVENT_CB, $background_event_cb);
	}

	/**
	 * @param mixed $socket_cb
	 */
	public function setSocketCb(mixed $socket_cb): void
	{
		$this->set(Constant::CONFIG_SOCKET_CB, $socket_cb);
	}

	/**
	 * @param mixed $connect_cb
	 */
	public function setConnectCb(mixed $connect_cb): void
	{
		$this->set(Constant::CONFIG_CONNECT_CB, $connect_cb);
	}

	/**
	 * @param mixed $closesocket_cb
	 */
	public function setClosesocketCb(mixed $closesocket_cb): void
	{
		$this->set(Constant::CONFIG_CLOSESOCKET_CB, $closesocket_cb);
	}

	/**
	 * @param mixed $open_cb
	 */
	public function setOpenCb(mixed $open_cb): void
	{
		$this->set(Constant::CONFIG_OPEN_CB, $open_cb);
	}

	/**
	 * @param mixed $opaque
	 */
	public function setOpaque(mixed $opaque): void
	{
		$this->set(Constant::CONFIG_OPAQUE, $opaque);
	}

	/**
	 * @param mixed $default_topic_conf
	 */
	public function setDefaultTopicConf(mixed $default_topic_conf): void
	{
		$this->set(Constant::CONFIG_DEFAULT_TOPIC_CONF, $default_topic_conf);
	}

	/**
	 * @param mixed $internal_termination_signal
	 */
	public function setInternalTerminationSignal(mixed $internal_termination_signal): void
	{
		$this->set(Constant::CONFIG_INTERNAL_TERMINATION_SIGNAL, $internal_termination_signal);
	}

	/**
	 * @param mixed $api_version_request
	 */
	public function setApiVersionRequest(mixed $api_version_request): void
	{
		$this->set(Constant::CONFIG_API_VERSION_REQUEST, $api_version_request);
	}

	/**
	 * @param mixed $api_version_request_timeout_ms
	 */
	public function setApiVersionRequestTimeoutMs(mixed $api_version_request_timeout_ms): void
	{
		$this->set(Constant::CONFIG_API_VERSION_REQUEST_TIMEOUT_MS, $api_version_request_timeout_ms);
	}

	/**
	 * @param mixed $api_version_fallback_ms
	 */
	public function setApiVersionFallbackMs(mixed $api_version_fallback_ms): void
	{
		$this->set(Constant::CONFIG_API_VERSION_FALLBACK_MS, $api_version_fallback_ms);
	}

	/**
	 * @param mixed $broker_version_fallback
	 */
	public function setBrokerVersionFallback(mixed $broker_version_fallback): void
	{
		$this->set(Constant::CONFIG_BROKER_VERSION_FALLBACK, $broker_version_fallback);
	}

	/**
	 * @param mixed $security_protocol
	 */
	public function setSecurityProtocol(mixed $security_protocol): void
	{
		$this->set(Constant::CONFIG_SECURITY_PROTOCOL, $security_protocol);
	}

	/**
	 * @param mixed $ssl_cipher_suites
	 */
	public function setSslCipherSuites(mixed $ssl_cipher_suites): void
	{
		$this->set(Constant::CONFIG_SSL_CIPHER_SUITES, $ssl_cipher_suites);
	}

	/**
	 * @param mixed $ssl_curves_list
	 */
	public function setSslCurvesList(mixed $ssl_curves_list): void
	{
		$this->set(Constant::CONFIG_SSL_CURVES_LIST, $ssl_curves_list);
	}

	/**
	 * @param mixed $ssl_sigalgs_list
	 */
	public function setSslSigalgsList(mixed $ssl_sigalgs_list): void
	{
		$this->set(Constant::CONFIG_SSL_SIGALGS_LIST, $ssl_sigalgs_list);
	}

	/**
	 * @param mixed $ssl_key_location
	 */
	public function setSslKeyLocation(mixed $ssl_key_location): void
	{
		$this->set(Constant::CONFIG_SSL_KEY_LOCATION, $ssl_key_location);
	}

	/**
	 * @param mixed $ssl_key_password
	 */
	public function setSslKeyPassword(mixed $ssl_key_password): void
	{
		$this->set(Constant::CONFIG_SSL_KEY_PASSWORD, $ssl_key_password);
	}

	/**
	 * @param mixed $ssl_key_pem
	 */
	public function setSslKeyPem(mixed $ssl_key_pem): void
	{
		$this->set(Constant::CONFIG_SSL_KEY_PEM, $ssl_key_pem);
	}

	/**
	 * @param mixed $ssl_key
	 */
	public function setSslKey(mixed $ssl_key): void
	{
		$this->set(Constant::CONFIG_SSL_KEY, $ssl_key);
	}

	/**
	 * @param mixed $ssl_certificate_location
	 */
	public function setSslCertificateLocation(mixed $ssl_certificate_location): void
	{
		$this->set(Constant::CONFIG_SSL_CERTIFICATE_LOCATION, $ssl_certificate_location);
	}

	/**
	 * @param mixed $ssl_certificate_pem
	 */
	public function setSslCertificatePem(mixed $ssl_certificate_pem): void
	{
		$this->set(Constant::CONFIG_SSL_CERTIFICATE_PEM, $ssl_certificate_pem);
	}

	/**
	 * @param mixed $ssl_certificate
	 */
	public function setSslCertificate(mixed $ssl_certificate): void
	{
		$this->set(Constant::CONFIG_SSL_CERTIFICATE, $ssl_certificate);
	}

	/**
	 * @param mixed $ssl_ca_location
	 */
	public function setSslCaLocation(mixed $ssl_ca_location): void
	{
		$this->set(Constant::CONFIG_SSL_CA_LOCATION, $ssl_ca_location);
	}

	/**
	 * @param mixed $ssl_ca
	 */
	public function setSslCa(mixed $ssl_ca): void
	{
		$this->set(Constant::CONFIG_SSL_CA, $ssl_ca);
	}

	/**
	 * @param mixed $ssl_ca_certificate_stores
	 */
	public function setSslCaCertificateStores(mixed $ssl_ca_certificate_stores): void
	{
		$this->set(Constant::CONFIG_SSL_CA_CERTIFICATE_STORES, $ssl_ca_certificate_stores);
	}

	/**
	 * @param mixed $ssl_crl_location
	 */
	public function setSslCrlLocation(mixed $ssl_crl_location): void
	{
		$this->set(Constant::CONFIG_SSL_CRL_LOCATION, $ssl_crl_location);
	}

	/**
	 * @param mixed $ssl_keystore_location
	 */
	public function setSslKeystoreLocation(mixed $ssl_keystore_location): void
	{
		$this->set(Constant::CONFIG_SSL_KEYSTORE_LOCATION, $ssl_keystore_location);
	}

	/**
	 * @param mixed $ssl_keystore_password
	 */
	public function setSslKeystorePassword(mixed $ssl_keystore_password): void
	{
		$this->set(Constant::CONFIG_SSL_KEYSTORE_PASSWORD, $ssl_keystore_password);
	}

	/**
	 * @param mixed $ssl_engine_location
	 */
	public function setSslEngineLocation(mixed $ssl_engine_location): void
	{
		$this->set(Constant::CONFIG_SSL_ENGINE_LOCATION, $ssl_engine_location);
	}

	/**
	 * @param mixed $ssl_engine_id
	 */
	public function setSslEngineId(mixed $ssl_engine_id): void
	{
		$this->set(Constant::CONFIG_SSL_ENGINE_ID, $ssl_engine_id);
	}

	/**
	 * @param mixed $ssl_engine_callback_data
	 */
	public function setSslEngineCallbackData(mixed $ssl_engine_callback_data): void
	{
		$this->set(Constant::CONFIG_SSL_ENGINE_CALLBACK_DATA, $ssl_engine_callback_data);
	}

	/**
	 * @param mixed $enable_ssl_certificate_verification
	 */
	public function setEnableSslCertificateVerification(mixed $enable_ssl_certificate_verification): void
	{
		$this->set(Constant::CONFIG_ENABLE_SSL_CERTIFICATE_VERIFICATION, $enable_ssl_certificate_verification);
	}

	/**
	 * @param mixed $ssl_endpoint_identification_algorithm
	 */
	public function setSslEndpointIdentificationAlgorithm(mixed $ssl_endpoint_identification_algorithm): void
	{
		$this->set(Constant::CONFIG_SSL_ENDPOINT_IDENTIFICATION_ALGORITHM, $ssl_endpoint_identification_algorithm);
	}

	/**
	 * @param mixed $ssl_certificate_verify_cb
	 */
	public function setSslCertificateVerifyCb(mixed $ssl_certificate_verify_cb): void
	{
		$this->set(Constant::CONFIG_SSL_CERTIFICATE_VERIFY_CB, $ssl_certificate_verify_cb);
	}

	/**
	 * @param mixed $sasl_mechanisms
	 */
	public function setSaslMechanisms(mixed $sasl_mechanisms): void
	{
		$this->set(Constant::CONFIG_SASL_MECHANISMS, $sasl_mechanisms);
	}

	/**
	 * @param mixed $sasl_mechanism
	 */
	public function setSaslMechanism(mixed $sasl_mechanism): void
	{
		$this->set(Constant::CONFIG_SASL_MECHANISM, $sasl_mechanism);
	}

	/**
	 * @param mixed $sasl_kerberos_service_name
	 */
	public function setSaslKerberosServiceName(mixed $sasl_kerberos_service_name): void
	{
		$this->set(Constant::CONFIG_SASL_KERBEROS_SERVICE_NAME, $sasl_kerberos_service_name);
	}

	/**
	 * @param mixed $sasl_kerberos_principal
	 */
	public function setSaslKerberosPrincipal(mixed $sasl_kerberos_principal): void
	{
		$this->set(Constant::CONFIG_SASL_KERBEROS_PRINCIPAL, $sasl_kerberos_principal);
	}

	/**
	 * @param mixed $sasl_kerberos_kinit_cmd
	 */
	public function setSaslKerberosKinitCmd(mixed $sasl_kerberos_kinit_cmd): void
	{
		$this->set(Constant::CONFIG_SASL_KERBEROS_KINIT_CMD, $sasl_kerberos_kinit_cmd);
	}

	/**
	 * @param mixed $sasl_kerberos_keytab
	 */
	public function setSaslKerberosKeytab(mixed $sasl_kerberos_keytab): void
	{
		$this->set(Constant::CONFIG_SASL_KERBEROS_KEYTAB, $sasl_kerberos_keytab);
	}

	/**
	 * @param mixed $sasl_kerberos_min_time_before_relogin
	 */
	public function setSaslKerberosMinTimeBeforeRelogin(mixed $sasl_kerberos_min_time_before_relogin): void
	{
		$this->set(Constant::CONFIG_SASL_KERBEROS_MIN_TIME_BEFORE_RELOGIN, $sasl_kerberos_min_time_before_relogin);
	}

	/**
	 * @param mixed $sasl_username
	 */
	public function setSaslUsername(mixed $sasl_username): void
	{
		$this->set(Constant::CONFIG_SASL_USERNAME, $sasl_username);
	}

	/**
	 * @param mixed $sasl_password
	 */
	public function setSaslPassword(mixed $sasl_password): void
	{
		$this->set(Constant::CONFIG_SASL_PASSWORD, $sasl_password);
	}

	/**
	 * @param mixed $sasl_oauthbearer_config
	 */
	public function setSaslOauthbearerConfig(mixed $sasl_oauthbearer_config): void
	{
		$this->set(Constant::CONFIG_SASL_OAUTHBEARER_CONFIG, $sasl_oauthbearer_config);
	}

	/**
	 * @param mixed $enable_sasl_oauthbearer_unsecure_jwt
	 */
	public function setEnableSaslOauthbearerUnsecureJwt(mixed $enable_sasl_oauthbearer_unsecure_jwt): void
	{
		$this->set(Constant::CONFIG_ENABLE_SASL_OAUTHBEARER_UNSECURE_JWT, $enable_sasl_oauthbearer_unsecure_jwt);
	}

	/**
	 * @param mixed $oauthbearer_token_refresh_cb
	 */
	public function setOauthbearerTokenRefreshCb(mixed $oauthbearer_token_refresh_cb): void
	{
		$this->set(Constant::CONFIG_OAUTHBEARER_TOKEN_REFRESH_CB, $oauthbearer_token_refresh_cb);
	}

	/**
	 * @param mixed $plugin_library_paths
	 */
	public function setPluginLibraryPaths(mixed $plugin_library_paths): void
	{
		$this->set(Constant::CONFIG_PLUGIN_LIBRARY_PATHS, $plugin_library_paths);
	}

	/**
	 * @param mixed $interceptors
	 */
	public function setInterceptors(mixed $interceptors): void
	{
		$this->set(Constant::CONFIG_INTERCEPTORS, $interceptors);
	}

	/**
	 * @param mixed $group_id
	 */
	public function setGroupId(mixed $group_id): void
	{
		$this->set(Constant::CONFIG_GROUP_ID, $group_id);
	}

	/**
	 * @param mixed $group_instance_id
	 */
	public function setGroupInstanceId(mixed $group_instance_id): void
	{
		$this->set(Constant::CONFIG_GROUP_INSTANCE_ID, $group_instance_id);
	}

	/**
	 * @param mixed $partition_assignment_strategy
	 */
	public function setPartitionAssignmentStrategy(mixed $partition_assignment_strategy): void
	{
		$this->set(Constant::CONFIG_PARTITION_ASSIGNMENT_STRATEGY, $partition_assignment_strategy);
	}

	/**
	 * @param mixed $session_timeout_ms
	 */
	public function setSessionTimeoutMs(mixed $session_timeout_ms): void
	{
		$this->set(Constant::CONFIG_SESSION_TIMEOUT_MS, $session_timeout_ms);
	}

	/**
	 * @param mixed $heartbeat_interval_ms
	 */
	public function setHeartbeatIntervalMs(mixed $heartbeat_interval_ms): void
	{
		$this->set(Constant::CONFIG_HEARTBEAT_INTERVAL_MS, $heartbeat_interval_ms);
	}

	/**
	 * @param mixed $group_protocol_type
	 */
	public function setGroupProtocolType(mixed $group_protocol_type): void
	{
		$this->set(Constant::CONFIG_GROUP_PROTOCOL_TYPE, $group_protocol_type);
	}

	/**
	 * @param mixed $coordinator_query_interval_ms
	 */
	public function setCoordinatorQueryIntervalMs(mixed $coordinator_query_interval_ms): void
	{
		$this->set(Constant::CONFIG_COORDINATOR_QUERY_INTERVAL_MS, $coordinator_query_interval_ms);
	}

	/**
	 * @param mixed $max_poll_interval_ms
	 */
	public function setMaxPollIntervalMs(mixed $max_poll_interval_ms): void
	{
		$this->set(Constant::CONFIG_MAX_POLL_INTERVAL_MS, $max_poll_interval_ms);
	}

	/**
	 * @param mixed $enable_auto_commit
	 */
	public function setEnableAutoCommit(mixed $enable_auto_commit): void
	{
		$this->set(Constant::CONFIG_ENABLE_AUTO_COMMIT, $enable_auto_commit);
	}

	/**
	 * @param mixed $auto_commit_interval_ms
	 */
	public function setAutoCommitIntervalMs(mixed $auto_commit_interval_ms): void
	{
		$this->set(Constant::CONFIG_AUTO_COMMIT_INTERVAL_MS, $auto_commit_interval_ms);
	}

	/**
	 * @param mixed $enable_auto_offset_store
	 */
	public function setEnableAutoOffsetStore(mixed $enable_auto_offset_store): void
	{
		$this->set(Constant::CONFIG_ENABLE_AUTO_OFFSET_STORE, $enable_auto_offset_store);
	}

	/**
	 * @param mixed $queued_min_messages
	 */
	public function setQueuedMinMessages(mixed $queued_min_messages): void
	{
		$this->set(Constant::CONFIG_QUEUED_MIN_MESSAGES, $queued_min_messages);
	}

	/**
	 * @param mixed $queued_max_messages_kbytes
	 */
	public function setQueuedMaxMessagesKbytes(mixed $queued_max_messages_kbytes): void
	{
		$this->set(Constant::CONFIG_QUEUED_MAX_MESSAGES_KBYTES, $queued_max_messages_kbytes);
	}

	/**
	 * @param mixed $fetch_wait_max_ms
	 */
	public function setFetchWaitMaxMs(mixed $fetch_wait_max_ms): void
	{
		$this->set(Constant::CONFIG_FETCH_WAIT_MAX_MS, $fetch_wait_max_ms);
	}

	/**
	 * @param mixed $fetch_message_max_bytes
	 */
	public function setFetchMessageMaxBytes(mixed $fetch_message_max_bytes): void
	{
		$this->set(Constant::CONFIG_FETCH_MESSAGE_MAX_BYTES, $fetch_message_max_bytes);
	}

	/**
	 * @param mixed $max_partition_fetch_bytes
	 */
	public function setMaxPartitionFetchBytes(mixed $max_partition_fetch_bytes): void
	{
		$this->set(Constant::CONFIG_MAX_PARTITION_FETCH_BYTES, $max_partition_fetch_bytes);
	}

	/**
	 * @param mixed $fetch_max_bytes
	 */
	public function setFetchMaxBytes(mixed $fetch_max_bytes): void
	{
		$this->set(Constant::CONFIG_FETCH_MAX_BYTES, $fetch_max_bytes);
	}

	/**
	 * @param mixed $fetch_min_bytes
	 */
	public function setFetchMinBytes(mixed $fetch_min_bytes): void
	{
		$this->set(Constant::CONFIG_FETCH_MIN_BYTES, $fetch_min_bytes);
	}

	/**
	 * @param mixed $fetch_error_backoff_ms
	 */
	public function setFetchErrorBackoffMs(mixed $fetch_error_backoff_ms): void
	{
		$this->set(Constant::CONFIG_FETCH_ERROR_BACKOFF_MS, $fetch_error_backoff_ms);
	}

	/**
	 * @param mixed $offset_store_method
	 */
	public function setOffsetStoreMethod(mixed $offset_store_method): void
	{
		$this->set(Constant::CONFIG_OFFSET_STORE_METHOD, $offset_store_method);
	}

	/**
	 * @param mixed $isolation_level
	 */
	public function setIsolationLevel(mixed $isolation_level): void
	{
		$this->set(Constant::CONFIG_ISOLATION_LEVEL, $isolation_level);
	}

	/**
	 * @param mixed $enable_partition_eof
	 */
	public function setEnablePartitionEof(mixed $enable_partition_eof): void
	{
		$this->set(Constant::CONFIG_ENABLE_PARTITION_EOF, $enable_partition_eof);
	}

	/**
	 * @param mixed $check_crcs
	 */
	public function setCheckCrcs(mixed $check_crcs): void
	{
		$this->set(Constant::CONFIG_CHECK_CRCS, $check_crcs);
	}

	/**
	 * @param mixed $allow_auto_create_topics
	 */
	public function setAllowAutoCreateTopics(mixed $allow_auto_create_topics): void
	{
		$this->set(Constant::CONFIG_ALLOW_AUTO_CREATE_TOPICS, $allow_auto_create_topics);
	}

	/**
	 * @param mixed $client_rack
	 */
	public function setClientRack(mixed $client_rack): void
	{
		$this->set(Constant::CONFIG_CLIENT_RACK, $client_rack);
	}

	/**
	 * @param mixed $transactional_id
	 */
	public function setTransactionalId(mixed $transactional_id): void
	{
		$this->set(Constant::CONFIG_TRANSACTIONAL_ID, $transactional_id);
	}

	/**
	 * @param mixed $transaction_timeout_ms
	 */
	public function setTransactionTimeoutMs(mixed $transaction_timeout_ms): void
	{
		$this->set(Constant::CONFIG_TRANSACTION_TIMEOUT_MS, $transaction_timeout_ms);
	}

	/**
	 * @param mixed $enable_idempotence
	 */
	public function setEnableIdempotence(mixed $enable_idempotence): void
	{
		$this->set(Constant::CONFIG_ENABLE_IDEMPOTENCE, $enable_idempotence);
	}

	/**
	 * @param mixed $enable_gapless_guarantee
	 */
	public function setEnableGaplessGuarantee(mixed $enable_gapless_guarantee): void
	{
		$this->set(Constant::CONFIG_ENABLE_GAPLESS_GUARANTEE, $enable_gapless_guarantee);
	}

	/**
	 * @param mixed $queue_buffering_max_messages
	 */
	public function setQueueBufferingMaxMessages(mixed $queue_buffering_max_messages): void
	{
		$this->set(Constant::CONFIG_QUEUE_BUFFERING_MAX_MESSAGES, $queue_buffering_max_messages);
	}

	/**
	 * @param mixed $queue_buffering_max_kbytes
	 */
	public function setQueueBufferingMaxKbytes(mixed $queue_buffering_max_kbytes): void
	{
		$this->set(Constant::CONFIG_QUEUE_BUFFERING_MAX_KBYTES, $queue_buffering_max_kbytes);
	}

	/**
	 * @param mixed $queue_buffering_max_ms
	 */
	public function setQueueBufferingMaxMs(mixed $queue_buffering_max_ms): void
	{
		$this->set(Constant::CONFIG_QUEUE_BUFFERING_MAX_MS, $queue_buffering_max_ms);
	}

	/**
	 * @param mixed $linger_ms
	 */
	public function setLingerMs(mixed $linger_ms): void
	{
		$this->set(Constant::CONFIG_LINGER_MS, $linger_ms);
	}

	/**
	 * @param mixed $message_send_max_retries
	 */
	public function setMessageSendMaxRetries(mixed $message_send_max_retries): void
	{
		$this->set(Constant::CONFIG_MESSAGE_SEND_MAX_RETRIES, $message_send_max_retries);
	}

	/**
	 * @param mixed $retries
	 */
	public function setRetries(mixed $retries): void
	{
		$this->set(Constant::CONFIG_RETRIES, $retries);
	}

	/**
	 * @param mixed $retry_backoff_ms
	 */
	public function setRetryBackoffMs(mixed $retry_backoff_ms): void
	{
		$this->set(Constant::CONFIG_RETRY_BACKOFF_MS, $retry_backoff_ms);
	}

	/**
	 * @param mixed $queue_buffering_backpressure_threshold
	 */
	public function setQueueBufferingBackpressureThreshold(mixed $queue_buffering_backpressure_threshold): void
	{
		$this->set(Constant::CONFIG_QUEUE_BUFFERING_BACKPRESSURE_THRESHOLD, $queue_buffering_backpressure_threshold);
	}

	/**
	 * @param mixed $compression_codec
	 */
	public function setCompressionCodec(mixed $compression_codec): void
	{
		$this->set(Constant::CONFIG_COMPRESSION_CODEC, $compression_codec);
	}

	/**
	 * @param mixed $compression_type
	 */
	public function setCompressionType(mixed $compression_type): void
	{
		$this->set(Constant::CONFIG_COMPRESSION_TYPE, $compression_type);
	}

	/**
	 * @param mixed $batch_num_messages
	 */
	public function setBatchNumMessages(mixed $batch_num_messages): void
	{
		$this->set(Constant::CONFIG_BATCH_NUM_MESSAGES, $batch_num_messages);
	}

	/**
	 * @param mixed $batch_size
	 */
	public function setBatchSize(mixed $batch_size): void
	{
		$this->set(Constant::CONFIG_BATCH_SIZE, $batch_size);
	}

	/**
	 * @param mixed $delivery_report_only_error
	 */
	public function setDeliveryReportOnlyError(mixed $delivery_report_only_error): void
	{
		$this->set(Constant::CONFIG_DELIVERY_REPORT_ONLY_ERROR, $delivery_report_only_error);
	}

	/**
	 * @param mixed $dr_cb
	 */
	public function setDrCb(mixed $dr_cb): void
	{
		$this->set(Constant::CONFIG_DR_CB, $dr_cb);
	}


	/**
	 * @param mixed $sticky_partitioning_linger_ms
	 */
	public function setStickyPartitioningLingerMs(mixed $sticky_partitioning_linger_ms): void
	{
		$this->set(Constant::CONFIG_STICKY_PARTITIONING_LINGER_MS, $sticky_partitioning_linger_ms);
	}


}
