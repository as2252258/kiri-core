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
	 * @return IClient
	 */
	public static function NewRequest(): IClient
	{
		return Coroutine::getCid() > 0 ? Client::NewRequest() : Curl::NewRequest();
	}


	/**
	 * @return Http2
	 */
	public static function http2()
	{
		return Snowflake::app()->http2;
	}

}
