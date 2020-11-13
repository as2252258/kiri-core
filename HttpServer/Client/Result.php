<?php
declare(strict_types=1);

namespace HttpServer\Client;

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
class Result
{
	public $code;
	public $message;
	public int $count = 0;
	public ?array $data;
	public ?array $header;
	public int $httpStatus = 200;

	public int $startTime = 0;
	public int $requestTime = 0;
	public float $runTime = 0;


	/**
	 * Result constructor.
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		$this->setAssignment($data);
	}


	/**
	 * @param $data
	 * @return $this
	 */
	public function setAssignment($data)
	{
		foreach ($data as $key => $val) {
			if (!property_exists($this, $key)) {
				continue;
			}
			$this->$key = $val;
		}
		return $this;
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
		return in_array($this->httpStatus, [100, 101, 200, 201, 202, 203, 204, 205, 206]);
	}

	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->data;
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
