<?php


namespace HttpServer\Client;


use HttpServer\Client\Client;
use HttpServer\Client\Curl;
use HttpServer\Client\IClient;
use JetBrains\PhpStorm\Pure;
use Kiri\Abstracts\Component;
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


}
