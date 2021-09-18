<?php
declare(strict_types=1);

namespace Http\Client;

use Exception;
use JetBrains\PhpStorm\Pure;


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
	public int|string $code;
	public string $message;
	public int $count = 0;

	public mixed $data;
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
				if (str_contains($val, ': ')) {
					$trim = explode(': ', $val);

					$_tmp[strtolower($trim[0])] = $trim[1];
				} else {
					$_tmp[$key] = $val;
				}
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
	#[Pure] public function isResultsOK(int $status = 0): bool
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
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @return string|int
	 */
	public function getCode(): string|int
	{
		return $this->code;
	}
}
