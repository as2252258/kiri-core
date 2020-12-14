<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:29
 */
declare(strict_types=1);

namespace HttpServer\Http\Formatter;


use HttpServer\Application;
use SimpleXMLElement;
use Swoole\Http\Response;
use HttpServer\IInterface\IFormatter;


/**
 * Class XmlFormatter
 * @package Snowflake\Snowflake\Http\Formatter
 */
class XmlFormatter extends Application implements IFormatter
{

	public ?string $data = '';

	/** @var Response */
	public Response $status;

	public array $header = [];

	/**
	 * @param $context
	 * @return $this
	 * @throws \Exception
	 */
	public function send($context): static
	{
		if (!is_string($context)) {
			// TODO: Implement send() method.
			$dom = new SimpleXMLElement('<xml/>');

			$this->toXml($dom, $context);

			$this->data = $dom->saveXML();
		}
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

	/**
	 * @param SimpleXMLElement $dom
	 * @param $data
	 */
	public function toXml(SimpleXMLElement $dom, $data)
	{
		foreach ($data as $key => $val) {
			if (is_numeric($key)) {
				$key = 'item' . $key;
			}
			if (is_array($val)) {
				$node = $dom->addChild($key);
				$this->toXml($node, $val);
			} else if (is_object($val)) {
				$val = get_object_vars($val);
				$node = $dom->addChild($key);
				$this->toXml($node, $val);
			} else {
				$dom->addChild($key, htmlspecialchars((string)$val));
			}
		}
	}

	public function clear(): void
	{
		$this->data = null;
		unset($this->data);
	}
}
