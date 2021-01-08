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

	/** @var mixed $data */
	public $data;
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
	public function setAssignment($data): static
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
	 * @return mixed
	 */
	public function __get($name): mixed
	{
		return $this->$name;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->$name = $value;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
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
	public function getTime(): array
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
	public function setAttr($key, $data): static
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
	public function isResultsOK($status = 0): bool
	{
		if (!$this->httpIsOk()) {
			return false;
		}
		return $this->code === $status;
	}

	/**
	 * @return bool
	 */
	public function httpIsOk(): bool
	{
		return in_array($this->httpStatus, [100, 101, 200, 201, 202, 203, 204, 205, 206]);
	}

	/**
	 * @return mixed
	 */
	public function getBody(): mixed
	{
		return $this->data;
	}

	/**
	 * @return mixed
	 */
	public function getMessage(): mixed
	{
		return $this->message;
	}

	/**
	 * @return mixed
	 */
	public function getCode(): mixed
	{
		return $this->code;
	}
}
