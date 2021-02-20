<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Exception;
use HttpServer\Abstracts\HttpService;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class TcpListen
 * @package Snowflake\Snowflake\Route
 */
class Handler extends HttpService
{

	/** @var Router */
	protected Router $router;

	/**
	 * Listen constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->router = Snowflake::app()->router;

		parent::__construct([]);
	}

	/**
	 * @param $config
	 * @param $handler
	 */
	public function group($config, $handler)
	{
		$this->router->group($config, $handler, $this);
	}


	/**
	 * @param $route
	 * @param $handler
	 * @return Handler|Node|null
	 * @throws ConfigException
	 */
	public function handler($route, $handler): Handler|Node|null
	{
		return $this->router->addRoute($route, $handler, 'receive');
	}

}
