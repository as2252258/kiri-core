<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:18
 */
declare(strict_types=1);
namespace HttpServer\Http\Formatter;

use Exception;
use HttpServer\Application;
use HttpServer\IInterface\IFormatter;

/**
 * Class JsonFormatter
 * @package Snowflake\Snowflake\Http\Formatter
 */
class JsonFormatter extends Application implements IFormatter
{
	public $data;

	public $status = 200;

	public $header = [];

	/**
	 * @param $data
	 * @return $this|IFormatter
	 * @throws Exception
	 */
	public function send($data)
	{
		if (!is_string($data)) {
			$data = json_encode($data);
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
		$this->data = null;
		unset($this->data);
	}
}
