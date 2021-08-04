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
use Snowflake\Exception\NotFindClassException;
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

	private mixed $endData;

	const FORMAT_MAPS = [
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
		if (empty($format)) {
			return $this;
		}
		$this->format = $format;
		return $this;
	}


	/**
	 * @param $content
	 * @return Response
	 */
	public function toHtml($content): static
	{
		$this->format = self::HTML;
		$this->endData = (string)$content;
		return $this;
	}


	/**
	 * @param $content
	 * @return Response
	 */
	public function toJson($content): static
	{
		$this->format = self::JSON;
		$this->endData = json_encode($content, JSON_UNESCAPED_UNICODE);
		return $this;
	}


	/**
	 * @param $content
	 * @return mixed
	 */
	public function toXml($content): static
	{
		$this->format = self::XML;
		$this->endData = $content;
		return $this;
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
		$this->cookies[] = func_get_args();
		return $this;
	}


	/**
	 * @param $statusCode
	 * @return Response
	 */
	public function setStatusCode($statusCode): static
	{
		$this->statusCode = $statusCode;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getResponseFormat(): string
	{
		return match ($this->format) {
			Response::HTML => 'text/html;charset=utf-8',
			Response::XML => 'application/xml;charset=utf-8',
			default => 'application/json;charset=utf-8',
		};
	}


	/**
	 * @param mixed $data
	 * @param SResponse|null $response
	 * @return Response
	 * @throws Exception
	 */
	public function getBuilder(mixed $data, SResponse $response = null): static
	{
		if ($response != null) {
			$this->configure($response);
		}
		return $this->setContent($data);
	}


	/**
	 * @param SResponse|null $response
	 * @throws Exception
	 */
	public function configure(SResponse $response = null): static
	{
		$response->setStatusCode($this->statusCode);
		$response->header('Content-Type', $this->getResponseFormat());
		$response->header('Run-Time', $this->getRuntime());
		if (!empty($this->headers)) {
			foreach ($this->headers as $name => $header) {
				$response->header($name, $header);
			}
		}
		if (!empty($this->cookies)) {
			foreach ($this->cookies as $header) {
				$response->setCookie(...$header);
			}
		}
		return $this;
	}


	/**
	 * @param mixed $content
	 * @param int $statusCode
	 * @param null $format
	 * @return Response
	 */
	public function setContent(mixed $content, int $statusCode = 200, $format = null): static
	{
		$this->endData = $content;
		$this->setStatusCode($statusCode);
		$this->setFormat($format);
		return $this;
	}


	/**
	 * @return string
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 */
	public function getContent(): string
	{
		if (empty($this->endData) || is_string($this->endData)) {
			return $this->endData;
		}

		$class = Response::FORMAT_MAPS[$this->format] ?? HtmlFormatter::class;

		return \di($class)->send($this->endData)->getData();
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
