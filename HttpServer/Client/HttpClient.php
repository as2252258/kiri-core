<?php


namespace HttpServer\Client;


use JetBrains\PhpStorm\Pure;
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
	#[Pure] public static function http2(): Http2
	{
		return Snowflake::app()->http2;
	}

}
