<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:18
 */
declare(strict_types=1);

namespace Http\Handler\Formatter;

use Http\Handler\Abstracts\HttpService;

/**
 * Class JsonFormatter
 * @package Kiri\Kiri\Http\Formatter
 */
class JsonFormatter extends HttpService implements IFormatter
{
	public mixed $data;

	public int $status = 200;

	public array $header = [];

	/**
	 * @param $context
	 * @return JsonFormatter
	 */
	public function send($context): static
	{
		if (!is_string($context)) {
			$context = json_encode($context);
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
