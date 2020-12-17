<?php
declare(strict_types=1);

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
		$logger = Snowflake::app()->logger;
		$logger->write($message, $category);
		$logger->insert();
	}

	/**
	 * @param $name
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($name): mixed
	{
		if (method_exists($this, $name)) {
			return $this->{$name}();
		}
		$handler = 'get' . ucfirst($name);
		if (method_exists($this, $handler)) {
			return $this->{$handler}();
		}
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		$message = sprintf('method %s::%s not exists.', get_called_class(), $name);
		throw new Exception($message);
	}

}
