<?php


namespace HttpServer\Client;


use Exception;
use Kiri\Abstracts\Component;
use Kiri\Kiri;
use Swoole\Coroutine;
use HttpServer\Client\Help\IClient;
use HttpServer\Client\Help\Client;
use HttpServer\Client\Help\Curl;

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
		return Coroutine::getCid() > -1 ? Client::NewRequest() : Curl::NewRequest();
	}


	/**
	 * @return Http2
	 * @throws Exception
	 */
	public static function http2(): Http2
	{
		return Kiri::app()->get('http2');
	}

}
