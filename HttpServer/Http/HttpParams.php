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
use Snowflake\Core\Xml;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class HttpParams
 * @package Snowflake\Snowflake\Http
 */
class HttpParams
{

	/** @var array|null */
	private ?array $_gets = [];


	/** @var array|null */
	private ?array $_posts = [];


	/** @var array|null */
	private ?array $_files = [];


	/** @var mixed|array */
	private mixed $_rawContent = [];


	/**
	 * @return mixed
	 */
	public function getRawContent(): mixed
	{
		return $this->_rawContent;
	}

	/**
	 * @return int
	 */
	public function offset(): int
	{
		return ($this->page() - 1) * $this->size();
	}

	/**
	 * @param mixed $data
	 * 批量添加数据
	 */
	public function setPosts(mixed $data): void
	{
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		$this->_posts = $data;
	}


	/**
	 * @param mixed $data
	 */
	public function addPosts(mixed $data): void
	{
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		foreach ($data as $key => $datum) {
			$this->_posts[$key] = $datum;
		}
	}


	/**
	 * @param array|null $files
	 */
	public function setFiles(?array $files): void
	{
		$this->_files = $files;
	}


	/**
	 * @param array|null $gets
	 */
	public function setGets(?array $gets): void
	{
		$this->_gets = $gets;
	}


	/**
	 * @param mixed $content
	 * @param Request $contextType
	 */
	public function setRawContent(mixed $content, Request $contextType)
	{
		if (empty($content)) {
			return;
		}
		$context_type = $contextType->headers->getContentType();
		if (str_contains($context_type, 'json')) {
			$this->addPosts(json_decode($content, true));
		} else if (str_contains($context_type, 'xml')) {
			$this->addPosts(Xml::toArray($content));
		} else {
			$this->_rawContent = $content;
		}
	}


	/**
	 * @return array|null
	 */
	public function getBody(): ?array
	{
		return $this->_posts;
	}


	/**
	 * @return int
	 */
	private function page(): int
	{
		return (int)$this->get('page', 1);
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed
	 */
	public function query($name, $default = null): mixed
	{
		return $this->_gets[$name] ?? $default;
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
		return $this->_gets[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @param null $defaultValue
	 * @param null $call
	 * @return mixed
	 */
	public function post($name, $defaultValue = null, $call = null): mixed
	{
		$data = $this->_posts[$name] ?? $defaultValue;
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
		return $this->_gets;
	}

	/**
	 * @return array
	 */
	public function params(): array
	{
		return array_merge($this->_posts ?? [], $this->_files ?? []);
	}

	/**
	 * @return array
	 */
	public function load(): array
	{
		return array_merge($this->_files ?? [], $this->_posts ?? [], $this->_gets ?? []);
	}

	/**
	 * @param $name
	 * @param array $defaultValue
	 * @return mixed
	 */
	public function array($name, array $defaultValue = []): mixed
	{
		return $this->_posts[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @return File|null
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function file($name): File|null
	{
		$param = $this->_files[$name] ?? null;
		if (!empty($param)) {
			$param['class'] = File::class;
			return Snowflake::createObject($param);
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param bool $isNeed
	 * @return mixed
	 * @throws RequestException
	 */
	private function required(string $name, bool $isNeed = false): mixed
	{
		$int = $this->_posts[$name] ?? NULL;
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
		return (int)$this->required($name, $isNeed);
	}

	/**
	 * @param string $name
	 * @param bool $isNeed
	 * @param int $round
	 * @return float|null
	 * @throws RequestException
	 */
	public function float(string $name, bool $isNeed = FALSE, int $round = 0): ?float
	{
		return (float)$this->required($name, $isNeed);
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
		return (string)$this->required($name, $isNeed);
	}

	/**
	 * @param string $name
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
		return (boolean)$this->required($name, $isNeed);
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
