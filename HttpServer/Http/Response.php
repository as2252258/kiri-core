<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 19:39
 */
declare(strict_types=1);

namespace HttpServer\Http;

use Exception;
use HttpServer\Abstracts\HttpService;
use HttpServer\Http\Formatter\HtmlFormatter;
use HttpServer\Http\Formatter\JsonFormatter;
use HttpServer\Http\Formatter\XmlFormatter;
use HttpServer\IInterface\IFormatter;
use Snowflake\Core\Help;
use Snowflake\Snowflake;
use Swoole\Http\Response as SResponse;

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

	public array $headers = [];
	public array $cookies = [];

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
		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		if ($format == self::HTML) {
			$response->header('Content-Type', 'text/html;charset=utf-8');
		} else if ($format == self::XML) {
			$response->header('Content-Type', 'application/xml;charset=utf-8');
		} else {
			$response->header('Content-Type', 'application/json;charset=utf-8');
		}
		return $this;
	}

	/**
	 * 清理无用数据
	 */
	public function clear(): void
	{
		$this->fd = 0;
		$this->format = null;
	}

	/**
	 * @param $content
	 * @return string
	 */
	public function toHtml($content): string
	{
		$this->format = self::HTML;

		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		$response->header('Content-Type', 'text/html;charset=utf-8');

		return (string)$content;
	}


	/**
	 * @param $content
	 * @return string|bool
	 */
	public function toJson($content): string|bool
	{
		$this->format = self::JSON;

		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		$response->header('Content-Type', 'application/json;charset=utf-8');

		return json_encode($content, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $content
	 * @return mixed
	 */
	public function toXml($content): mixed
	{
		$this->format = self::XML;

		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		$response->header('Content-Type', 'application/xml;charset=utf-8');

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
		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		$response->header($key, $value);
		return $this;
	}

	/**
	 * @param $name
	 * @param null $value
	 * @param null $expires
	 * @param null $path
	 * @param null $domain
	 * @param null $secure
	 * @param null $httponly
	 * @param null $samesite
	 * @param null $priority
	 * @return Response
	 */
	public function addCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null, $priority = null): static
	{
		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);
		$response->cookie(...func_get_args());
		return $this;
	}

	/**
	 * @param mixed $context
	 * @param int $statusCode
	 * @return bool
	 * @throws Exception
	 */
	public function send(mixed $context = '', int $statusCode = 200): mixed
	{
		$sendData = $this->parseData($context);
		$this->statusCode = $statusCode;
		if (!Context::hasContext(SResponse::class)) {
			$this->printResult($sendData);
		} else {
			$this->sendData($sendData);
		}
		return $sendData;
	}

	/**
	 * @param $context
	 * @return mixed
	 * @throws Exception
	 */
	private function parseData($context): mixed
	{
		if (!empty($context)) {
			/** @var IFormatter $class */
			$class = $this->_format_maps[$this->format] ?? HtmlFormatter::class;

			$di = Snowflake::getDi()->get($class);
			$context = $di->send($context)->getData();
		}
		return $context;
	}

	/**
	 * @param $result
	 * @return void
	 * @throws Exception
	 */
	private function printResult($result): void
	{
		$result = Help::toString($result);
		fire('CONSOLE_END');
		if (str_contains((string)$result, 'Event::rshutdown(): Event::wait()')) {
			return;
		}
		$string = 'Command Result: ' . PHP_EOL . PHP_EOL;
		if (empty($result)) {
			$string .= 'success!' . PHP_EOL . PHP_EOL;
		} else {
			$string .= $result . PHP_EOL . PHP_EOL;
		}
		echo $string . 'Command End!' . PHP_EOL;
	}

	/**
	 * @param $sendData
	 * @throws Exception
	 */
	private function sendData($sendData): void
	{
		/** @var SResponse $response */
		$response = Context::getContext(SResponse::class);

		if (!isset($response->header['Content-Type'])) {
			$response->header('Content-Type', 'application/json;charset=utf-8');
		}
		$response->header('Run-Time', $this->getRuntime());
		$response->status($this->statusCode);
		$response->end($sendData);

        Context::remove(SResponse::class);
	}


	/**
	 * @param $url
	 * @param array $param
	 * @return int
	 */
	public function redirect($url, array $param = []): mixed
	{
		if (!empty($param)) {
			$url .= '?' . http_build_query($param);
		}
		$url = ltrim($url, '/');
		if (!preg_match('/^http/', $url)) {
			$url = '/' . $url;
		}
		/** @var SResponse $response */
		$response = Context::getContext('response');
		if (!empty($response)) {
			return $response->redirect($url);
		}
		return false;
	}


	/**
	 * @return static
	 * @throws Exception
	 */
	public static function create(): static
	{
		$ciResponse = Snowflake::app()->get('response');
		$ciResponse->startTime = microtime(true);
		$ciResponse->format = self::JSON;
		return $ciResponse;
	}


	/**
	 * @param int $statusCode
	 * @param string $message
	 * @return mixed
	 * @throws Exception
	 */
	public function close(int $statusCode = 200, string $message = ''): mixed
	{
		return $this->send($message, $statusCode);
	}


	/**
	 * @param $clientId
	 * @param int $statusCode
	 * @param string $message
	 * @return mixed
	 */
	public function closeClient($clientId, int $statusCode = 200, string $message = ''): mixed
	{
		$socket = Snowflake::getWebSocket();
		if (!$socket->exist($clientId)) {
			return true;
		}
		return $socket->close($clientId, true);
	}


	/**
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 * @param int $sleep
	 * @return string
	 */
	public function sendFile(string $path, int $offset = 0, int $limit = 1024000, int $sleep = 0): string
	{
		$open = fopen($path, 'r');

		$stat = fstat($open);


		/** @var SResponse $response */
		$response = Context::getContext('response');
		$response->header('Content-length', $stat['size']);
		while ($file = fread($open, $limit)) {
			$response->write($file);
			fseek($open, $offset);
			if ($sleep > 0) sleep($sleep);
			if ($offset >= $stat['size']) {
				break;
			}
			$offset += $limit;
		}
		$response->end();
		return '';
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function getRuntime(): string
	{
		return sprintf('%.5f', microtime(TRUE) - request()->getStartTime());
	}

}
