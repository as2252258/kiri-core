<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Abstracts\Config;
use Snowflake\Core\JSON;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
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
		$this->debug('server shutdown~');

		$this->system_mail('server shutdown~');
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_SHUTDOWN);
	}

}
