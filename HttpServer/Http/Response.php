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
use HttpServer\Http\Formatter\FileFormatter;
use HttpServer\Http\Formatter\HtmlFormatter;
use HttpServer\Http\Formatter\JsonFormatter;
use HttpServer\Http\Formatter\XmlFormatter;
use HttpServer\IInterface\IFormatter;
use Kiri\Exception\NotFindClassException;
use Server\ResponseInterface;
use Server\ServerManager;
use Swoole\Http\Response as SResponse;

/**
 * Class Response
 * @package Kiri\Kiri\Http
 */
class Response extends HttpService implements ResponseInterface
{

	const JSON = 'json';
	const XML = 'xml';
	const HTML = 'html';
	const FILE = 'file';

	/** @var ?string */
	private ?string $format = null;

	/** @var int */
	public int $statusCode = 200;

	public array $headers = [];
	public array $cookies = [];

	private float $startTime = 0;

	private mixed $endData;

	private array $_clientInfo = [];

	const FORMAT_MAPS = [
		self::JSON => JsonFormatter::class,
		self::XML  => XmlFormatter::class,
		self::HTML => HtmlFormatter::class,
		self::FILE => FileFormatter::class,
	];

	public int $fd = 0;
	private int $clientId = 0;
	private int $reactorId = 0;


	/**
	 * @param int $int
	 * @param int $reID
	 */
	public function setClientId(int $int, int $reID)
	{
		$this->clientId = $int;
		$this->reactorId = $reID;
	}


	/**
	 * @param array $clientInfo
	 */
	public function setClientInfo(array $clientInfo)
	{
		$this->_clientInfo = $clientInfo;
	}


	/**
	 * @return mixed
	 */
	public function getClientInfo(): mixed
	{
		if (!empty($this->_clientInfo)) {
			return $this->_clientInfo;
		}
		$server = ServerManager::getContext()->getServer();
		return $server->getClientInfo($this->clientId, $this->reactorId);
	}


	/**
	 * @return string
	 */
	public function getFormat(): string
	{
		return $this->format;
	}


	/**
	 * @return int
	 */
	public function getClientId(): int
	{
		return $this->clientId;
	}


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
	 * @param string $path
	 * @param bool $isChunk
	 * @param int $offset
	 * @param int $limit
	 * @return static
	 * @throws Exception
	 */
	public function sendFile(string $path, bool $isChunk = false, int $offset = 0, int $limit = 10240): static
	{
		$this->format = self::FILE;
		if (!file_exists($path)) {
			throw new Exception('File `' . $path . '` not exists.');
		}
		$this->endData = ['path' => $path, 'isChunk' => $isChunk, 'limit' => $limit, 'offset' => $offset];
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
			Response::FILE => 'application/octet-stream',
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
	public function getBuilder(mixed $data, SResponse $response = null): ResponseInterface
	{
		if ($response != null) {
			$this->configure($response);
		}
		return $this->setContent($data);
	}


	/**
	 * @param SResponse $response
	 * @return Response
	 * @throws Exception
	 */
	public function configure(SResponse $response): static
	{
		$response->setStatusCode($this->statusCode);
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
	 * @return ResponseInterface
	 */
	public function setContent(mixed $content): ResponseInterface
	{
		$this->endData = $content;
		return $this;
	}


	/**
	 * @return IFormatter
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 */
	public function getContent(): IFormatter
	{
		$class = Response::FORMAT_MAPS[$this->format] ?? HtmlFormatter::class;
		return \di($class)->send($this->endData);
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
			return $response->redirect($url, 302);
		}
		return false;
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
