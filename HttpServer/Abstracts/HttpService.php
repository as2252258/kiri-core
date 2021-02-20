<?php
declare(strict_types=1);

namespace HttpServer\Abstracts;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;


/**
 * Class HttpService
 * @package HttpServer\Abstracts
 */
abstract class HttpService extends Component
{


	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	protected function write($message, $category = 'app')
	{
		$logger = Snowflake::app()->getLogger();
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
