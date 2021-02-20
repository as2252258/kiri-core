<?php
declare(strict_types=1);


namespace HttpServer\Route;


use HttpServer\Abstracts\HttpService;
use HttpServer\Exception\AuthException;
use HttpServer\Route\Filter\BodyFilter;
use HttpServer\Route\Filter\FilterException;
use HttpServer\Route\Filter\HeaderFilter;
use HttpServer\Route\Filter\QueryFilter;
use Exception;
use Snowflake\Snowflake;

/**
 * Class Filter
 * @package Snowflake\Snowflake\Route
 */
class Filter extends HttpService
{

	/** @var Filter\Filter[] */
	private array $_filters = [];

	/** @var array */
	public array $grant = [];

	/**
	 * @param array $value
	 * @return BodyFilter|bool
	 * @throws Exception
	 */
	public function setBody(array $value): bool|BodyFilter
	{
		if (empty($value)) {
			return true;
		}

		/** @var BodyFilter $class */
		$class = Snowflake::createObject(BodyFilter::class);
		$class->rules = [];
		$class->params = Input()->params();

		return $this->_filters[] = $class;
	}


	/**
	 * @param array $value
	 * @return HeaderFilter|bool
	 * @throws Exception
	 */
	public function setHeader(array $value): HeaderFilter|bool
	{
		if (empty($value)) {
			return true;
		}

		/** @var HeaderFilter $class */
		$class = Snowflake::createObject(HeaderFilter::class);
		$class->rules = [];
		$class->params = request()->headers->getHeaders();

		return $this->_filters[] = $class;
	}


	/**
	 * @param array $value
	 * @return QueryFilter|bool
	 * @throws Exception
	 */
	public function setQuery(array $value): QueryFilter|bool
	{
		if (empty($value)) {
			return true;
		}

		/** @var QueryFilter $class */
		$class = Snowflake::createObject(QueryFilter::class);
		$class->rules = [];
		$class->params = request()->headers->getHeaders();

		return $this->_filters[] = $class;
	}


	/**
	 * @throws Exception
	 */
	public function handler(): bool
	{
		if (($error = $this->filters()) !== true) {
			throw new FilterException($error);
		}
		if (!$this->grant()) {
			throw new AuthException('Authentication error.');
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private function filters(): bool
	{
		if (empty($this->_filters)) {
			return true;
		}
		foreach ($this->_filters as $filter) {
			if (!$filter->check()) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return mixed
	 */
	private function grant(): mixed
	{
		if (!is_callable($this->grant, true)) {
			return true;
		}
		return call_user_func($this->grant);
	}

}
