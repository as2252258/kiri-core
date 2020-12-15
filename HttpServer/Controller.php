<?php
declare(strict_types=1);

namespace HttpServer;


use Database\DatabasesProviders;
use HttpServer\Client\Client;
use HttpServer\Client\Curl;
use HttpServer\Client\Http2;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Exception;
use HttpServer\Route\Router;
use Kafka\Producer;
use Snowflake\Abstracts\BaseGoto;
use Snowflake\Annotation\Annotation;
use Snowflake\Cache\Memcached;
use Snowflake\Cache\Redis;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Jwt\Jwt;
use Snowflake\Pool\Connection;
use Snowflake\Pool\Pool as SPool;
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 * @property BaseGoto $goto
 * @property Annotation $annotation
 * @property Event $event
 * @property Router $router
 * @property SPool $pool
 * @property \Redis|Redis $redis
 * @property Server $server
 * @property DatabasesProviders $db
 * @property Connection $connections
 * @property Memcached $memcached
 * @property Logger $logger
 * @property Jwt $jwt
 * @property Client $client
 * @property Producer $kafka
 * @property Curl $curl
 * @property Http2 $http2
 */
class Controller extends Application
{

	/** @var null|HttpParams $input */
	public null|HttpParams $input;


	/** @var null|HttpHeaders */
	public null|HttpHeaders $headers;


	/** @var null|Request */
	public null|Request $request;


	/**
	 * Controller constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
	}

	/**
	 * @param null|HttpParams $input
	 */
	public function setInput(?HttpParams $input): void
	{
		$this->input = $input;
	}

	/**
	 * @param ?HttpHeaders $headers
	 */
	public function setHeaders(?HttpHeaders $headers): void
	{
		$this->headers = $headers;
	}

	/**
	 * @param ?Request $request
	 */
	public function setRequest(?Request $request): void
	{
		$this->request = $request;
	}

	/**
	 * @return ?HttpParams
	 * @throws Exception
	 */
	public function getInput(): ?HttpParams
	{
		if (!$this->input) {
			$this->input = $this->getRequest()->params;
		}
		return $this->input;
	}

	/**
	 * @return ?HttpHeaders
	 * @throws Exception
	 */
	public function getHeaders(): ?HttpHeaders
	{
		if (!$this->headers) {
			$this->headers = $this->getRequest()->headers;
		}
		return $this->headers;
	}

	/**
	 * @return Request|null
	 */
	public function getRequest(): ?Request
	{
		if (!$this->request) {
			$this->request = Snowflake::app()->request;
		}
		return $this->request;
	}


	/**
	 * @param $methods
	 * @return mixed
	 * @throws ComponentException
	 */
	public function __get($methods): mixed
	{
		// TODO: Change the autogenerated stub
		if (property_exists($this, $methods)) {
			return $this->$methods;
		}

		$method = 'get' . ucfirst($methods);
		if (method_exists($this, $method)) {
			return $this->{$method}();
		}

		return Snowflake::app()->get($methods);
	}


}
