<?php


namespace HttpServer\Route;


use Exception;
use HttpServer\Application;

/**
 * Class TcpListen
 * @package BeReborn\Route
 */
class Handler extends Application
{

	/** @var Router */
	protected $router;

	/**
	 * Listen constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->router = \BeReborn::$app->getRouter();

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
