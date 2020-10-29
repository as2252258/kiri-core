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
	 * @param $data
	 * @return $this
	 * @throws \Exception
	 */
	public function send($data)
	{
		if (!is_string($data)) {
			// TODO: Implement send() method.
			$dom = new SimpleXMLElement('<xml/>');

			$this->toXml($dom, $data);

			$this->data = $dom->saveXML();
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getData()
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
				$dom->addChild($key, htmlspecialchars($val));
			}
		}
	}

	public function clear()
	{
		$this->data = null;
		unset($this->data);
	}
}
