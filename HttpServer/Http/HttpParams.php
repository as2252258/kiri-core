<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */
declare(strict_types=1);

namespace HttpServer\Http;

use Exception;
use HttpServer\Exception\RequestException;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Core\Json;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class HttpParams
 * @package Snowflake\Snowflake\Http
 */
class HttpParams
{

	private string|array $body = [];

	/** @var array */
	private array $gets = [];

	/** @var array */
	private array $files = [];
	private array $socket = [];

	/**
	 * HttpParams constructor.
	 * @param $body
	 * @param $get
	 * @param $files
	 * @param array $socket
	 */
	public function __construct(mixed $body, ?array $get, ?array $files, $socket = [])
	{
		$this->gets = $get ?? [];
		$this->files = $files ?? [];
		$this->socket = $socket ?? [];
		$this->body = $body ?? '';

		var_dump($body);
	}

	/**
	 * @return int
	 */
	public function offset(): int
	{
		return ($this->page() - 1) * $this->size();
	}

	/**
	 * @param array $data
	 * 批量添加数据
	 */
	public function setPosts(array $data)
	{
		if (!is_array($data)) {
			return;
		}
		foreach ($data as $key => $vla) {
			$this->body[$key] = $vla;
		}
	}


	/**
	 * 删除参数
	 */
	public function clearBody()
	{
		$this->body = [];
	}


	/**
	 * 删除参数
	 */
	public function clearGet()
	{
		$this->gets = [];
	}


	/**
	 * 清空文件上传信息
	 */
	public function clearFile()
	{
		$this->files = [];
	}


	/**
	 * @return mixed
	 */
	public function getBody(): mixed
	{
		return $this->body;
	}


	/**
	 * @return mixed
	 */
	public function getBodyAndClear(): mixed
	{
		$data = $this->body['body'];
		$this->clearBody();
		return $data;
	}


	/**
	 * @param string $key
	 * @param string $value
	 */
	public function addGetParam(string $key, string $value)
	{
		$this->gets[$key] = $value;
	}

	/**
	 * @return int
	 */
	private function page(): int
	{
		return (int)$this->get('page', 1);
	}

	/**
	 * @return int
	 */
	public function size(): int
	{
		return (int)$this->get('size', 20);
	}


	/**
	 * @param $name
	 * @param null $defaultValue
	 * @param null $call
	 * @return mixed
	 */
	public function get($name, $defaultValue = null, $call = null): mixed
	{
		return $this->gets[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @param null $defaultValue
	 * @param null $call
	 * @return mixed
	 */
	public function post($name, $defaultValue = null, $call = null): mixed
	{
		$data = $this->body[$name] ?? $defaultValue;
		if ($call !== null) {
			$data = call_user_func($call, $data);
		}
		return $data;
	}

	/**
	 * @param $name
	 * @return bool|string
	 * @throws Exception
	 */
	public function json($name): bool|string
	{
		$data = $this->array($name);
		if (empty($data)) {
			return Json::encode([]);
		} else if (!is_array($data)) {
			return Json::encode([]);
		}
		return Json::encode($data);
	}

	/**
	 * @return array
	 */
	public function gets(): array
	{
		return $this->gets;
	}

	/**
	 * @return array
	 */
	#[Pure] public function params(): array
	{
		return array_merge($this->body ?? [], $this->files ?? []);
	}

	/**
	 * @return array
	 */
	#[Pure] public function load(): array
	{
		return array_merge($this->files ?? [], $this->body ?? [], $this->gets ?? [], $this->socket ?? []);
	}

	/**
	 * @param $name
	 * @param array $defaultValue
	 * @return mixed
	 */
	public function array($name, $defaultValue = []): mixed
	{
		return $this->body[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @return File|null
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function file($name): File|null
	{
		if (!isset($this->files[$name])) {
			return null;
		}
		$param = $this->files[$name];
		$param['class'] = File::class;
		return Snowflake::createObject($param);
	}

	/**
	 * @param $name
	 * @param bool $isNeed
	 * @return mixed
	 * @throws RequestException
	 */
	private function required(string $name, bool $isNeed = false): mixed
	{
		$body = array_merge($this->body ?? [], $this->socket ?? []);

		$int = $body[$name] ?? NULL;
		if (is_null($int) && $isNeed === true) {
			throw new RequestException("You need to add request parameter $name");
		}
		return $int;
	}

	/**
	 * @param string $name
	 * @param bool $isNeed
	 * @param array|int|null $min
	 * @param int|null $max
	 * @return int|null
	 * @throws RequestException
	 */
	public function int(string $name, bool $isNeed = FALSE, array|int|null $min = NULL, int|null $max = NULL): ?int
	{
		$int = $this->required($name, $isNeed);
		if (is_null($int)) return null;
		if (is_array($min)) {
			list($min, $max) = $min;
		}
		$length = strlen((string)$int);
		if (!is_numeric($int) || intval($int) != $int) {
			throw new RequestException("The request parameter $name must integer.");
		}
		$this->between($length, $min, $max);
		return (int)$int;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param int $round
	 * @return float|null
	 * @throws RequestException
	 */
	public function float(string $name, bool $isNeed = FALSE, $round = 0): ?float
	{
		$int = $this->required($name, $isNeed);
		if ($int === null) {
			return null;
		}
		if ($round > 0) {
			return round(floatval($int), $round);
		} else {
			return floatval($int);
		}
	}

	/**
	 * @param string $name
	 * @param bool $isNeed
	 * @param int|array|null $length
	 *
	 * @return string|null
	 * @throws RequestException
	 */
	public function string(string $name, bool $isNeed = FALSE, int|array|null $length = NULL): ?string
	{
		$string = $this->required($name, $isNeed);
		if ($string === null || $length === null) {
			return $string;
		}
		if (is_numeric($length)) {
			$length = [$length, $length];
		}
		return $this->between($string, $length[0], $length[1] ?? $length[0]);
	}

	/**
	 * @param $string
	 * @param $min
	 * @param $max
	 * @return mixed
	 * @throws RequestException
	 */
	private function between($string, $min, $max): mixed
	{
		$_length = mb_strlen((string)$string);
		if ($min !== NULL && $_length < $min) {
			throw new RequestException("The minimum value cannot be lower than $min, has length $_length");
		}
		if ($max !== NULL && $_length > $max) {
			throw new RequestException("Maximum cannot exceed $max, has length " . $_length);
		}
		return $string;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 *
	 * @return string|null
	 * @throws RequestException
	 */
	public function email(string $name, bool $isNeed = FALSE): ?string
	{
		$email = $this->required($name, $isNeed);
		if ($email === null) {
			return null;
		}
		if (!preg_match('/^\w+([.-_]\w+)+@\w+(\.\w+)+$/', $email)) {
			throw new RequestException("Request parameter $name is in the wrong format", 4001);
		}
		return $email;
	}


	/**
	 * @param string $name
	 * @param bool $isNeed
	 *
	 * @return bool
	 * @throws RequestException
	 */
	public function bool(string $name, bool $isNeed = FALSE): bool
	{
		$email = $this->required($name, $isNeed);
		if ($email === null) {
			return false;
		}
		return (bool)$email;
	}

	/**
	 * @param string $name
	 * @param int|null $default
	 *
	 * @return int|string|null
	 * @throws RequestException
	 */
	public function timestamp(string $name, int|null $default = NULL): null|int|string
	{
		$value = $this->required($name, false);
		if ($value === null) {
			return $default;
		}
		if (!is_numeric($value)) {
			throw new RequestException('The request param :attribute not is a timestamp value');
		}
		if (strlen((string)$value) != 10) {
			throw new RequestException('The request param :attribute not is a timestamp value');
		}
		if (!date('YmdHis', $value)) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}

	/**
	 * @param string $name
	 * @param string|null $default
	 *
	 * @return string|null
	 * @throws RequestException
	 */
	public function datetime(string $name, string $default = NULL): string|null
	{
		$value = $this->required($name, false);
		if ($value === null) {
			return $default;
		}
		$match = '/^\d{4}.*?([1-12]).*([1-31]).*?[0-23].*?[0-59].*?[0-59].*?$/';
		$match = preg_match($match, $value, $result);
		if (!$match || $result[0] != $value) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}

	/**
	 * @param string $name
	 * @param string|null $default
	 *
	 * @return string|null
	 * @throws RequestException
	 */
	public function date(string $name, string $default = NULL): string|null
	{
		$value = $this->required($name, false);
		if ($value === null) {
			return $default;
		}
		$match = '/^\d{4}.*?([1-12]).*([1-31])$/';
		$match = preg_match($match, $value, $result);
		if (!$match || $result[0] != $value) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}


	/**
	 * @param string $name
	 * @param string|null $default
	 * @return string|null
	 * @throws RequestException
	 */
	public function ip(string $name, string $default = NULL): string|null
	{
		$value = $this->required($name, false);
		if ($value == NULL) {
			return $default;
		}
		$match = preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $value, $result);
		if (!$match || $result[0] != $value) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	#[Pure] public function __get($name): mixed
	{
		$load = $this->load();

		return $load[$name] ?? null;
	}

}
