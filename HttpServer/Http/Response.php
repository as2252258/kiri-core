<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */
declare(strict_types=1);

namespace HttpServer\Http;

use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Formatter\HtmlFormatter;
use HttpServer\Http\Formatter\JsonFormatter;
use HttpServer\Http\Formatter\XmlFormatter;
use Exception;
use JetBrains\PhpStorm\Pure;
use Snowflake\Core\Help;
use Snowflake\Snowflake;
use Swoole\Http\Response as SResponse;
use Swoole\Http2\Response as S2Response;

/**
 * Class Response
 * @package Snowflake\Snowflake\Http
 */
class Response extends HttpService
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';

	/** @var ?string */
	public ?string $format = null;

	/** @var int */
	public int $statusCode = 200;

	public ?SResponse $response = null;
	public bool $isWebSocket = false;
	public array $headers = [];

	private float $startTime = 0;

	private array $_format_maps = [
		self::JSON => JsonFormatter::class,
		self::XML  => XmlFormatter::class,
		self::HTML => HtmlFormatter::class
	];

	public int $fd = 0;

	/**
	 * @param $format
	 * @return $this
	 */
	public function setFormat($format): static
	{
		$this->format = $format;
		return $this;
	}

	/**
	 * 清理无用数据
	 */
	public function clear(): void
	{
		$this->fd = 0;
		$this->isWebSocket = false;
		$this->format = null;
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
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
	 * @param $content
	 * @return mixed
	 */
	public function toHtml($content): mixed
	{
		$this->format = self::HTML;
		return $content;
	}


	/**
	 * @param $content
	 * @return mixed
	 */
	public function toJson($content): mixed
	{
		$this->format = self::JSON;
		return $content;
	}


	/**
	 * @param $content
	 * @return mixed
	 */
	public function toXml($content): mixed
	{
		$this->format = self::XML;
		return $content;
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function sender(): mixed
	{
		return $this->send(func_get_args());
	}

	/**
	 * @param $key
	 * @param $value
	 * @return Response
	 */
	public function addHeader($key, $value): static
	{
		$this->headers[$key] = $value;
		return $this;
	}

	/**
	 * @return bool
	 */
	private function isClient(): bool
	{
		return !($this->response instanceof SResponse) && !($this->response instanceof S2Response);
	}

	/**
	 * @param string $context
	 * @param int $statusCode
	 * @param null $response
	 * @return bool
	 * @throws Exception
	 */
	public function send($context = '', $statusCode = 200, $response = null): mixed
	{
		$sendData = $this->parseData($context);
		if ($response instanceof SResponse) {
			$this->response = $response;
		}
		if ($this->response instanceof SResponse) {
			return $this->sendData($sendData, $statusCode);
		} else {
			if (!empty(request()->fd)) {
				return '';
			}
			return $this->printResult($sendData);
		}
	}

	/**
	 * @param $context
	 * @return mixed
	 * @throws Exception
	 */
	private function parseData($context): mixed
	{
		if ($context === null) {
			return '';
		}
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
	private function printResult($result): string
	{
		$result = Help::toString($result);
		$string = PHP_EOL . 'Command Result: ' . PHP_EOL . PHP_EOL;
		if (empty($result)) {
			$string .= 'success!' . PHP_EOL . PHP_EOL;
		} else {
			$string .= $result . PHP_EOL . PHP_EOL;
		}
		$string .= 'Command End!' . PHP_EOL . PHP_EOL;
		print_r($string);

		$event = Snowflake::app()->getEvent();
		$event->trigger('CONSOLE_END');

		return $result;
	}

	/**
	 * @param $sendData
	 * @param $status
	 * @return mixed
	 */
	private function sendData($sendData, $status): mixed
	{
		$this->response->status($status);
		$this->response->header('Content-Type', $this->getContentType());
		$this->response->header('Run-Time', $this->getRuntime());
		if (!empty($sendData)) {
			$this->response->end($this->headers($sendData));
		} else {
			$this->response->end();
		}
		$this->response = null;
		unset($this->response);
		return $sendData;
	}


	/**
	 * @param $sendData
	 * @return string
	 */
	private function headers($sendData): string
	{
		if (!empty($this->headers) && is_array($this->headers)) {
			foreach ($this->headers as $key => $header) {
				$this->response->header($key, $header);
			}
			$this->headers = [];
		}
		return $sendData == null ? '' : $sendData;
	}


	/**
	 * @param $url
	 * @param array $param
	 * @return int
	 */
	public function redirect($url, array $param = []): int
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
	public static function create($response = null): mixed
	{
		$ciResponse = Context::setContext('response', new Response());
		$ciResponse->response = $response;
		$ciResponse->startTime = microtime(true);
		$ciResponse->format = self::JSON;
		return $ciResponse;
	}


	/**
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 * @param int $sleep
	 * @return string
	 */
	public function sendFile(string $path, $offset = 0, $limit = 1024000, $sleep = 0): string
	{
		$open = fopen($path, 'r');

		$name = explode(DIRECTORY_SEPARATOR, $path);

		$stat = fstat($open);
		$this->response->setHeader('Content-Length', $stat['size']);
		$this->response->setHeader('Content-Type', 'model/gltf-binary');
		$this->response->setHeader('content-encoding', 'gzip');
		$this->response->setHeader('Content-Disposition', ' attachment; filename="' . end($name) . '"');

		while ($file = fread($open, $limit)) {
			$this->response->write($file);
			fseek($open, $offset);
			if ($sleep > 0) {
				sleep($sleep);
			}
			if ($offset >= $stat['size']) {
				break;
			}
			$offset += $limit;
		}

		$this->response->end();
		$this->response = null;

		return '';
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
	#[Pure] public function getRuntime(): string
	{
		return sprintf('%.5f', microtime(TRUE) - $this->startTime);
	}

}
