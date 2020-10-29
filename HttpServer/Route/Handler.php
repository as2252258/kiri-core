<?php
declare(strict_types=1);


namespace HttpServer\Route;


use Exception;
use HttpServer\Application;
use Snowflake\Snowflake;

/**
 * Class TcpListen
 * @package Snowflake\Snowflake\Route
 */
class Handler extends Application
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
	 * @return Handler
	 */
	public function handler($route, $handler)
	{
		return $this->router->addRoute($route, $handler, 'receive');
	}

}
