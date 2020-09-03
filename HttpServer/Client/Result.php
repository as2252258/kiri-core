<?php

namespace HttpServer\Client;

use HttpServer\Application;
use Exception;

/**
 * Class Result
 *
 * @package app\components
 *
 * @property $code
 * @property $message
 * @property $count
 * @property $data
 */
class Result extends Application
{
	public $code;
	public $message;
	public $count = 0;
	public $data;
	public $header;
	public $httpStatus = 200;

	public $startTime = 0;
	public $requestTime = 0;
	public $runTime = 0;

	public $statusCode = [100, 101, 200, 201, 202, 203, 204, 205, 206];


	/**
	 * Result constructor.
	 * @param array $data
	 * @param array $config
	 */
	public function __construct(array $data, $config = [])
	{
		parent::__construct($config);

		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		return $this->$name;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this|void
	 */
	public function __set($name, $value)
	{
		$this->$name = $value;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		$_tmp = [];
		if (!is_array($this->header)) {
			return $_tmp;
		}
		foreach ($this->header as $key => $val) {
			if ($key == 0) {
				$_tmp['pro'] = $val;
			} else {
				$trim = explode(': ', $val);

				$_tmp[strtolower($trim[0])] = $trim[1];
			}
		}
		return $_tmp;
	}


	/**
	 * @return array
	 */
	public function getTime()
	{
		return [
			'startTime'   => $this->startTime,
			'requestTime' => $this->requestTime,
			'runTime'     => $this->runTime,
		];
	}

	/**
	 * @param $key
	 * @param $data
	 * @return $this
	 * @throws Exception
	 */
	public function setAttr($key, $data)
	{
		if (!property_exists($this, $key)) {
			throw new Exception('未查找到相应对象属性');
		}
		$this->$key = $data;
		return $this;
	}

	/**
	 * @param int $status
	 * @return bool
	 */
	public function isResultsOK($status = 0)
	{
		if (!$this->httpIsOk()) {
			return false;
		}
		return $this->code === $status;
	}

	/**
	 * @return bool
	 */
	public function httpIsOk()
	{
		return in_array($this->httpStatus, $this->statusCode);
	}

	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		$headers = $this->getHeaders();
		if (!isset($headers['content-type'])) {
			return $this->data;
		}
		if (!is_string($this->data)) {
			return $this->data;
		}
		switch (trim($headers['content-type'])) {
			case 'application/json; encoding=utf-8';
			case 'application/json;';
			case 'application/json';
			case 'text/plain';
				return json_decode($this->data, true);
				break;
		}
		return $this->data;
	}

	/**
	 * @param $key
	 * @param $data
	 * @return $this
	 */
	public function append($key, $data)
	{
		$this->data[$key] = $data;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return mixed
	 */
	public function getCode()
	{
		return $this->code;
	}
}
