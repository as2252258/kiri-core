<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +---------------------------------------------------------------------------
// | SWAN [ $_SWANBR_SLOGAN_$ ]
// +---------------------------------------------------------------------------
// | Copyright $_SWANBR_COPYRIGHT_$
// +---------------------------------------------------------------------------
// | Version  $_SWANBR_VERSION_$
// +---------------------------------------------------------------------------
// | Licensed ( $_SWANBR_LICENSED_URL_$ )
// +---------------------------------------------------------------------------
// | $_SWANBR_WEB_DOMAIN_$
// +---------------------------------------------------------------------------

namespace Kafka;

use RdKafka\Conf;
use RdKafka\TopicConf;
use Snowflake\Abstracts\Component;

/**
 * +------------------------------------------------------------------------------
 * Kafka protocol since Kafka v0.8
 * +------------------------------------------------------------------------------
 *
 * @package
 * @version $_SWANBR_VERSION_$
 * @copyright Copyleft
 * @author $_SWANBR_AUTHOR_$
 * +------------------------------------------------------------------------------
 */
class Producer extends Component
{

	/**
	 * @param $topic
	 * @param $brokers
	 * @param $message
	 * @param null $key
	 * @param int $timeout
	 */
	public function delivery($topic, $brokers, $message, $key = null, $timeout = 5)
	{
		$conf = new Conf();
		$conf->set('metadata.broker.list', $brokers);
		$conf->setDrmSgCb(function ($kafka, $message) {
//				$this->debug(var_export($message, true));
		});
		$conf->setErrorCb(function ($kafka, $err, $reason) {
			$this->error(sprintf("Kafka error: %s (reason: %s)", rd_kafka_err2str($err), $reason));
		});

		$cf = new TopicConf();
		$cf->set('request.required.acks', 1);

		$rk = new \RdKafka\Producer($conf);
		$topic = $rk->newTopic($topic, $cf);
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, $message, $key);
		$rk->poll($timeout);
		$rk->flush($timeout);
	}
}
