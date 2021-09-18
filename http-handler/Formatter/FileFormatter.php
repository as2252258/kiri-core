<?php

namespace Http\Handler\Formatter;

use Exception;
use Http\Handler\Abstracts\HttpService;
use Swoole\Http\Response;


/**
 *
 */
class FileFormatter extends HttpService implements IFormatter
{

	public mixed $data;

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
