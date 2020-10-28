<?php


namespace Kafka;


use Exception;
use HttpServer\Server;
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

		$kafka = SConfig::get('kafka');
		if (empty($kafka) || !($kafka['enable'] ?? false)) {
			return;
		}
		if (!isset($kafka['topic']) || empty($kafka['topic'])) {
			throw new Exception('kafka configure error.');
		}
		if (!isset($kafka['version']) || empty($kafka['version'])) {
			throw new Exception('kafka configure error.');
		}
		if (!isset($kafka['brokers']) || empty($kafka['brokers'])) {
			throw new Exception('kafka configure error.');
		}
		if (!isset($kafka['groupId']) || empty($kafka['groupId'])) {
			throw new Exception('kafka configure error.');
		}
		if (!is_array($kafka['topics'])) {
			throw new Exception('Add kafka topics must is array.');
		}
		$server->addProcess('kafka', Kafka::class);
	}

}
