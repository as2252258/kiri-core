<?php


namespace Snowflake\Observer;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Core\ArrayAccess;
use Snowflake\Core\Dtl;

/**
 * Class Subscribe
 * @package Snowflake\Observer
 */
class Subscribe extends Component
{

	public $subscribes = [];

	public $params = [];


	/**
	 * @param $name
	 * @param $callback
	 * @param array $params
	 * @return Subscribe
	 * @throws Exception
	 */
	public function subscribe($name, $callback, array $params = [])
	{
		if (!is_callable($callback, true)) {
			throw new Exception('Subscribe must need callback.');
		}
		$this->subscribes[$name] = $callback;
		$this->params[$name] = $params;
		return $this;
	}


	/**
	 * @param $params
	 * @param null $name
	 * @return mixed|void
	 * @throws Exception
	 */
	public function publish($name = null, $params = [])
	{
		if (empty($name)) {
			return $this->release_all($params);
		}
		if (!isset($this->subscribes[$name])) {
			throw new Exception('Subscribe ' . $name . ' not found.');
		}
		$merge = $this->merge($name, $params);
		return $this->subscribes[$name](new Dtl($merge));
	}


	/**
	 * @param $params
	 */
	private function release_all($params)
	{
		foreach ($this->subscribes as $name => $subscribe) {
			$merge = $this->merge($this->params[$name] ?? [], $params);
			$subscribe(new Dtl($merge));
		}
	}


	/**
	 * @param $name
	 * @param $params
	 * @return mixed
	 */
	private function merge($name, $params)
	{
		if (!isset($this->params[$name])) {
			return $params;
		}
		return merge($this->params[$name], $params);
	}


}
