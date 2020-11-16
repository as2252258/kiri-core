<?php


namespace HttpServer\Client;


use Snowflake\Abstracts\Component;
use Swoole\Coroutine;


/**
 * Class ClientDriver
 * @package HttpServer\Client
 */
class ClientDriver extends Component
{

	/**
	 * @param $name
	 * @return IClient
	 */
	public function __call($name): IClient
	{
		if (Coroutine::getCid() > 0) {
			return Client::NewRequest();
		} else {
			return Curl::NewRequest();
		}
	}

}
