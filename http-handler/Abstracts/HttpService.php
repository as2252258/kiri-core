<?php
declare(strict_types=1);

namespace Http\Handler\Abstracts;


use Exception;
use Kiri\Abstracts\Component;
use Kiri\Kiri;


/**
 * Class HttpService
 * @package Http\Abstracts
 */
abstract class HttpService extends Component
{


	/**
	 * @param $message
	 * @param string $category
	 * @throws Exception
	 */
	protected function write($message, string $category = 'app')
	{
		$logger = Kiri::app()->getLogger();
		$logger->write($message, $category);
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
		$message = sprintf('method %s::%s not exists.', static::class, $name);
		throw new Exception($message);
	}

}
