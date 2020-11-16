<?php


namespace HttpServer\Client;


use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;
use Swoole\Coroutine;


/**
 * Class ClientDriver
 * @package HttpServer\Client
 */
class HttpClient extends Component
{

	/**
	 * @param $name
	 * @return IClient
	 */
	public static function NewRequest($name): IClient
	{
		if (Coroutine::getCid() > 0) {
			return Client::NewRequest();
		} else {
			return Curl::NewRequest();
		}
	}


	/**
	 * @return Http2
	 */
	public static function http2()
	{
		return Snowflake::app()->http2;
	}

}
