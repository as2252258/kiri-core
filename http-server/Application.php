<?php


namespace HttpServer;


use Exception;
use HttpServer\Abstracts\HttpService;
use Snowflake\Snowflake;

/**
 * Class Application
 * @package HttpServer
 */
class Application extends HttpService
{

	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	protected function write($message, $category = 'app')
	{
		$logger = Snowflake::get()->logger;
		$logger->write($message, $category);
		$logger->insert();
	}

	/**
	 * @param $methods
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($methods)
	{
		if (method_exists($this, $methods)) {
			return $this->{$methods}();
		}
		$handler = 'get' . ucfirst($methods);
		if (method_exists($this, $handler)) {
			return $this->{$handler}();
		}
		if (property_exists($this, $methods)) {
			return $this->$methods;
		}
		$message = sprintf('method %s::%s not exists.', get_called_class(), $methods);
		throw new Exception($message);
	}

}
