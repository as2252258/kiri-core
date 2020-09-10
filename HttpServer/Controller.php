<?php


namespace HttpServer;


use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Exception;
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 */
class Controller extends Application
{

	/** @var HttpParams $input */
	public $input;


	/** @var HttpHeaders */
	public $headers;


	/** @var Request */
	public $request;

	/**
	 * @param HttpParams $input
	 */
	public function setInput(HttpParams $input): void
	{
		$this->input = $input;
	}

	/**
	 * @param HttpHeaders $headers
	 */
	public function setHeaders(HttpHeaders $headers): void
	{
		$this->headers = $headers;
	}

	/**
	 * @param Request $request
	 */
	public function setRequest(Request $request): void
	{
		$this->request = $request;
	}

	/**
	 * @return HttpParams
	 * @throws Exception
	 */
	public function getInput(): HttpParams
	{
		if (!$this->input) {
			$this->input = $this->getRequest()->params;
		}
		return $this->input;
	}

	/**
	 * @return HttpHeaders
	 * @throws Exception
	 */
	public function getHeaders(): HttpHeaders
	{
		if (!$this->headers) {
			$this->headers = $this->getRequest()->headers;
		}
		return $this->headers;
	}

	/**
	 * @return Request
	 * @throws Exception
	 */
	public function getRequest(): Request
	{
		if (!$this->request) {
			$this->request = Snowflake::app()->request;
		}
		return $this->request;
	}

	/**
	 * @param $name
	 * @return mixed|null
	 * @throws Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		$app = Snowflake::app();
		if ($app->has($name)) {
			return $app->get($name);
		}

		return parent::__get($name);
	}

}
