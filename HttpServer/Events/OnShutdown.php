<?php


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

		$workers = glob(storage(null, 'worker') . '/*');
		foreach ($workers as $worker) {
			$content = file_get_contents($worker);
			posix_kill($content, 9);
		}

		$content = '[error]: ' . date('Y-m-d H:i:s') . PHP_EOL;
		$content .= print_r(swoole_last_error(), true);

		Snowflake::writeFile(storage('shutdown.log'), $content, FILE_APPEND);

		$this->system_mail('server shutdown~');
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_SHUTDOWN);
	}

}
