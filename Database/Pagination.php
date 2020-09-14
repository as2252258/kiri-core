<?php


namespace Database;


use Snowflake\Abstracts\Component;
use Closure;
use Exception;
use Snowflake\Snowflake;
use Swoole\Coroutine;

/**
 * Class Pagination
 * @package Database
 */
class Pagination extends Component
{

	/** @var ActiveQuery */
	private $activeQuery;

	/** @var int 从第几个开始查 */
	private $_offset = 0;

	/** @var int 每页数量 */
	private $_limit = 100;

	/** @var int 最大查询数量 */
	private $_max = 0;

	/** @var int 当前已查询数量 */
	private $_length = 0;

	/** @var Closure */
	private $_callback;

	/** @var Coroutine\Channel */
	private $_channel;

	/**
	 * PaginationIteration constructor.
	 * @param ActiveQuery $activeQuery
	 * @param array $config
	 */
	public function __construct(ActiveQuery $activeQuery, array $config = [])
	{
		parent::__construct($config);
		$this->activeQuery = $activeQuery;
//		$this->_channel = new Coroutine\Channel();
	}


	/**
	 * @param Closure|array $callback
	 * @throws Exception
	 */
	public function setCallback($callback)
	{
		if (!is_callable($callback, true)) {
			throw new Exception('非法回调函数~');
		}
		$this->_callback = $callback;
	}


	/**
	 * @param int $number
	 * @return Pagination
	 */
	public function setOffset(int $number)
	{
		if ($number < 0) {
			$number = 0;
		}
		$this->_offset = $number;
		return $this;
	}


	/**
	 * @param int $number
	 * @return Pagination
	 */
	public function setLimit(int $number)
	{
		if ($number < 1) {
			$number = 100;
		} else if ($number > 5000) {
			$number = 5000;
		}
		$this->_limit = $number;
		return $this;
	}


	/**
	 * @param int $number
	 * @return Pagination|void
	 */
	public function setMax(int $number)
	{
		if ($number < 0) {
			return $this;
		}
		$this->_max = $number;
		return $this;
	}


	/**
	 * @param array $param
	 * @return array
	 */
	public function plunk($param = [])
	{
		if ($this->_max > 0 && $this->_length >= $this->_max) {
			return $this->output();
		}
		[$length, $data] = $this->get();

		$this->runner($data, $param);

		unset($data);
		if ($length < $this->_limit) {
			return $this->output();
		}
		return $this->plunk($param);
	}


	/**
	 * @return array
	 */
	public function output()
	{
		//		while ($this->_channel->length() > 0) {
//			$array[] = $this->_channel->pop();
//		}
//		$this->_channel->close();
		return [];
	}


	/**
	 * @param $data
	 * @param $param
	 */
	private function runner($data, $param)
	{
		if (Snowflake::inCoroutine()) {
			Coroutine::create($this->_callback, $data, $param);
		} else {
			call_user_func($this->_callback, $data, $param);
		}
	}


	/**
	 * @return array|Collection
	 */
	private function get()
	{
		if ($this->_length + $this->_limit > $this->_max) {
			$this->_limit = $this->_length + $this->_limit - $this->_max;
		}
		$data = $this->activeQuery->limit($this->_offset, $this->_limit)->get();
		$this->_offset += $this->_limit;
		$this->_length += $data->size();
		return [$data->size(), $data];
	}

}
