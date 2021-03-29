<?php
declare(strict_types=1);

namespace HttpServer;


use HttpServer\Abstracts\HttpService;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Exception;
use ReflectionException;
use Snowflake\Abstracts\TraitApplication;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 */
class Controller extends HttpService
{

	use TraitApplication;

	/** @var null|HttpParams $input */
	public null|HttpParams $input;


	/** @var null|HttpHeaders */
	public null|HttpHeaders $headers;


	/** @var null|Request */
	public null|Request $request;


	/**
	 * Controller constructor.
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
	}


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$annotation = Snowflake::getAnnotation();
		$annotation->injectProperty($this);
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
		if (!$this->headers && $this->getRequest()) {
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
	 * @param $name
	 * @return mixed
	 * @throws ComponentException
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function __get($name): mixed
	{
		// TODO: Change the autogenerated stub
		if (property_exists($this, $name)) {
			return $this->$name;
		}

		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method)) {
			return $this->{$method}();
		}

		return Snowflake::app()->get($name);
	}


}
