<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Config;
use Snowflake\Core\JSON;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Server;
use Closure;

/**
 * Class OnShutdown
 * @package HttpServer\Events
 */
class OnShutdown extends Callback
{

	/**
	 * @param Server $server
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
//		$this->system_mail('server shutdown~');
		$event = Snowflake::get()->getEvent();
		if (!$event->exists(Event::SERVER_SHUTDOWN)) {
			return;
		}
		$event->trigger(Event::SERVER_SHUTDOWN);
	}

}
