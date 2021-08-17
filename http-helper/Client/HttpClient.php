<?php


namespace Http\Client;


use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Component;
use ReflectionException;
use Swoole\Coroutine;

/**
 * Class ClientDriver
 * @package Http\Client
 * @mixin Client
 */
class HttpClient extends Component
{

	/**
	 * @return IClient
	 */
	public static function NewRequest(): IClient
	{
		if (Coroutine::getCid() > -1) {
			return Client::NewRequest();
		}
		return Curl::NewRequest();
	}


	/**
	 * @return Curl
	 */
	#[Pure] public function getCurl(): Curl
	{
		return Curl::NewRequest();
	}


	/**
	 * @return Client
	 */
	#[Pure] public function getCoroutine(): Client
	{
		return Client::NewRequest();
	}


	/**
	 * @param string $name
	 * @param array $arguments
	 * @return void
	 */
	public function __call(string $name, array $arguments)
	{
		if (!method_exists($this, $name)) {
			return static::NewRequest()->{$name}(...$arguments);
		}
		return $this->{$name}(...$arguments);
	}


}
