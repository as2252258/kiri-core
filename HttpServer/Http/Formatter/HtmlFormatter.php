<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:51
 */
declare(strict_types=1);

namespace HttpServer\Http\Formatter;


use Exception;
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
	public Response $status;

	public array $header = [];

	/**
	 * @param $context
	 * @return $this
	 * @throws Exception
	 */
	public function send($context): static
	{
		if (!is_string($context)) {
			$context = JSON::encode($context);
		}
		$this->data = $context;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getData(): mixed
	{
		$data = $this->data;
		$this->clear();
		return $data;
	}

	public function clear(): void
	{
		$this->data = null;
		unset($this->data);
	}
}
