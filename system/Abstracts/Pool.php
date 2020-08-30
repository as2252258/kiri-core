<?php


namespace Snowflake\Abstracts;


use Exception;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * Class Pool
 * @package BeReborn\Pool
 */
abstract class Pool extends Component
{

	/** @var Channel[] */
	private $_items = [];

	public $max = 60;

	private $is_ticker = false;

	/**
	 * @param $name
	 * @param false $isMaster
	 * @param int $max
	 */
	public function initConnections($name, $isMaster = false, $max = 60)
	{
		$name = $this->name($name, $isMaster);
		if (isset($this->_items[$name]) &&
			$this->_items[$name] instanceof Channel) {
			return;
		}
		$this->_items[$name] = new Channel($max);
	}

	/**
	 * @param $name
	 * @param int $timeout
	 * @return mixed
	 */
	protected function get($name, $timeout = -1)
	{
		if ($this->is_ticker) {
			Coroutine::sleep(1);
		}
		if ($timeout != -1) {
			$client = $this->_items[$name]->pop($timeout);
		} else {
			$client = $this->_items[$name]->pop(60);
		}
		if (!$this->checkCanUse(...$client)) {
			unset($client);
			return [0, null];
		} else {
			return $client;
		}
	}

	/**
	 * @param $cds
	 * @param false $isMaster
	 * @return string
	 */
	public function name($cds, $isMaster = false)
	{
		return hash('sha1', $cds . ($isMaster ? 'master' : 'slave'));
	}


	/**
	 * @param $time
	 * @param $client
	 * @return mixed
	 * 检查连接可靠性
	 * @throws Exception
	 */
	public function checkCanUse($time, $client)
	{
		throw new Exception('Undefined system processing function.');
	}

	/**
	 * @param $name
	 * @throws Exception
	 */
	public function desc($name)
	{
		throw new Exception('Undefined system processing function.');
	}

	/**
	 * @param array $config
	 * @param $isMaster
	 * @throws Exception
	 */
	public function getConnection(array $config, $isMaster)
	{
		throw new Exception('Undefined system processing function.');
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function hasItem($name)
	{
		return $this->hasLength($name) > 0;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function hasLength($name)
	{
		if (!isset($this->_items[$name])) {
			return 0;
		}
		return $this->_items[$name]->length();
	}


	/**
	 * @param $name
	 * @param $client
	 */
	public function push($name, $client)
	{
		if (!isset($this->_items[$name])) {
			return;
		}
		if (!$this->_items[$name]->isFull()) {
			$this->_items[$name]->push([time(), $client]);
		}
	}


	/**
	 * @param $name
	 */
	public function clean($name)
	{
		if (!isset($this->_items[$name])) {
			return;
		}
		while ([$time, $client] = $this->_items[$name]->pop(0.001)) {
			unset($client);
		}
	}

}
