<?php


namespace Snowflake;


use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Snowflake\Abstracts\Component;
use Swoole\Process\Pool;

/**
 * Class Processes
 * @package Snowflake
 */
class Processes extends Component
{

	public $processes = [];


	/**
	 * 构建服务
	 * @throws Exception
	 */
	public function initCore()
	{
		$manager = Snowflake::get()->server;

		$serverConfig = Config::get('servers', true);

		return $manager->initCore($serverConfig);
	}


	/**
	 * @throws Exception
	 */
	public function start()
	{
		$server = $this->initCore();
		$server->start();
	}
}
