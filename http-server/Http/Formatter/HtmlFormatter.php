<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:51
 */

namespace HttpServer\Http\Formatter;


use Snowflake\Core\JSON;
use HttpServer\Application;
use Swoole\Http\Response;
use HttpServer\IInterface\IFormatter;

/**
 * Class HtmlFormatter
 * @package Snowflake\Snowflake\Http\Formatter
 */
class HtmlFormatter extends Application implements IFormatter
{

	public $data;

	/** @var Response */
	public $status;

	public $header = [];

	/**
	 * @param $data
	 * @return $this
	 * @throws \Exception
	 */
	public function send($data)
	{
		if (!is_string($data)) {
			$data = JSON::encode($data);
		}
		$this->data = $data;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		$data = $this->data;
		$this->clear();
		return $data;
	}

	public function clear()
	{
		unset($this->data);
	}
}
