<?php
declare(strict_types=1);

namespace Kafka;


use Exception;
use HttpServer\Server;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Config as SConfig;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;


/**
 * Class QueueProviders
 * @package Queue
 */
class KafkaProviders extends Providers
{

	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		/** @var Server $server */
		$server = $application->get('server');
		$application->set('kafka', ['class' => Producer::class]);
		$kafka = SConfig::get('kafka');
		if (empty($kafka) || !($kafka['enable'] ?? false)) {
			return;
		}
		if (!extension_loaded('rdkafka')) {
			return;
		}

		$kafkaServers = Config::get('kafka.servers', []);
		if (empty($kafkaServers)) {
			return;
		}

		foreach ($kafkaServers as $index => $kafkaServer) {
			$server->addProcess('kafka_' . $index, Kafka::class, $kafkaServer);
		}
	}

}
