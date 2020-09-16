<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */

namespace HttpServer\Http;

use HttpServer\Application;
use HttpServer\Http\Formatter\HtmlFormatter;
use HttpServer\Http\Formatter\JsonFormatter;
use HttpServer\Http\Formatter\XmlFormatter;
use Exception;
use Snowflake\Core\Help;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Http\Response as SResponse;
use Swoole\Http2\Response as S2Response;

/**
 * Class Response
 * @package Snowflake\Snowflake\Http
 */
class Response extends Application
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';

	/** @var string */
	public $format = null;

	/** @var int */
	public $statusCode = 200;

	/** @var SResponse */
	public $response;
	public $isWebSocket = false;
	public $headers = [];

	private $startTime = 0;

	private $_format_maps = [
		self::JSON => JsonFormatter::class,
		self::XML  => XmlFormatter::class,
		self::HTML => HtmlFormatter::class
	];

	public $fd = 0;

	/**
	 * @param $format
	 * @return $this
	 */
	public function setFormat($format)
	{
		$this->format = $format;
		return $this;
	}

	/**
	 * 清理无用数据
	 */
	public function clear()
	{
		$this->fd = 0;
		$this->isWebSocket = false;
		$this->format = null;
	}

	/**
	 * @return string
	 */
	public function getContentType()
	{
		if ($this->format == null || $this->format == static::JSON) {
			return 'application/json;charset=utf-8';
		} else if ($this->format == static::XML) {
			return 'application/xml;charset=utf-8';
		} else {
			return 'text/html;charset=utf-8';
		}
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function sender()
	{
		return $this->send(func_get_args());
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function addHeader($key, $value)
	{
		/** @var Response $response */
		if (!($response = Context::getContext('response'))) {
			return;
		}
		if (
			!($response->response instanceof SResponse) ||
			!($response->response instanceof S2Response)
		) {
			return;
		}
		$response->response->header($key, $value);
	}

	/**
	 * @param string $context
	 * @param int $statusCode
	 * @param null $response
	 * @return bool
	 * @throws Exception
	 */
	public function send($context = '', $statusCode = 200, $response = null)
	{
		$sendData = $this->parseData($context);
		if ($response instanceof SResponse) {
			$this->response = $response;
		}
		if ($this->response instanceof SResponse) {
			return $this->sendData($sendData, $statusCode);
		} else {
			return $this->printResult($sendData);
		}
	}

	/**
	 * @param $context
	 * @return mixed
	 * @throws Exception
	 */
	private function parseData($context)
	{
		if (isset($this->_format_maps[$this->format])) {
			$config['class'] = $this->_format_maps[$this->format];
		} else {
			$config['class'] = HtmlFormatter::class;
		}
		$formatter = Snowflake::createObject($config);
		return $formatter->send($context)->getData();
	}

	/**
	 * @param $result
	 * @return string
	 * @throws Exception
	 */
	private function printResult($result)
	{
		$result = Help::toString($result);
		$string = PHP_EOL . 'Command Result: ' . PHP_EOL . PHP_EOL;
		if (empty($result)) {
			$string .= 'success!' . PHP_EOL . PHP_EOL;
		} else {
			$string .= $result . PHP_EOL . PHP_EOL;
		}
		$string .= 'Command End!' . PHP_EOL . PHP_EOL;
		echo $string;

		$event = Snowflake::app()->event;
		$event->trigger('CONSOLE_END');

		return $result;
	}

	/**
	 * @param $sendData
	 * @param $status
	 * @return mixed
	 */
	private function sendData($sendData, $status)
	{
		$this->response->status($status);
		$this->response->header('Content-Type', $this->getContentType());
		$this->response->header('Access-Control-Allow-Origin', '*');
		$this->response->header('Run-Time', $this->getRuntime());
		$this->response->end($sendData);
		return $sendData;
	}

	/**
	 * @param $url
	 * @param array $param
	 * @return int
	 */
	public function redirect($url, array $param = [])
	{
		if (!empty($param)) {
			$url .= '?' . http_build_query($param);
		}
		$url = ltrim($url, '/');
		if (!preg_match('/^http/', $url)) {
			$url = '/' . $url;
		}
		return $this->response->redirect($url);
	}

	/**
	 * @param null $response
	 * @return mixed
	 */
	public static function create($response = null)
	{
		$ciResponse = Context::setContext('response', new Response());
		$ciResponse->response = $response;
		$ciResponse->startTime = microtime(true);
		$ciResponse->format = self::JSON;
		return $ciResponse;
	}


	/**
	 * @throws Exception
	 */
	public function sendNotFind()
	{
		$this->format = static::HTML;
		$this->send('', 404);
	}

	/**
	 * @return string
	 */
	public function getRuntime()
	{
		return sprintf('%.5f', microtime(TRUE) - $this->startTime);
	}

}
