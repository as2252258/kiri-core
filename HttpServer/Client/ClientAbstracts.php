<?php


namespace HttpServer\Client;


use Closure;
use JetBrains\PhpStorm\Pure;
use Snowflake\Abstracts\Component;
use Snowflake\Core\Help;
use Swoole\Coroutine\System;


/**
 * Class ClientAbstracts
 * @package HttpServer\Client
 */
abstract class ClientAbstracts extends Component implements IClient
{

	const POST = 'post';
	const UPLOAD = 'upload';
	const GET = 'get';
	const DELETE = 'delete';
	const OPTIONS = 'options';
	const HEAD = 'head';
	const PUT = 'put';

	private string $host = '';

	private array $header = [];

	private int $timeout = 0;

	private ?Closure $callback = null;
	private string $method = 'get';

	private bool $isSSL = false;
	private string $agent = '';
	private string $errorCodeField = '';
	private string $errorMsgField = '';
	private bool $use_swoole = false;

	private string $ssl_cert_file = '';
	private string $ssl_key_file = '';
	private string $ca = '';
	private int $port = 80;

	/** @var string $_message 错误信息 */
	private string $_message = '';
	private string $_data = '';

	private int $connect_timeout = 1;


	/**
	 * @return static
	 */
	#[Pure] public static function NewRequest(): static
	{
		return new static();
	}


	protected function cleanData(): void
	{
		$this->_data = '';
	}


	/**
	 * @param $path
	 * @param array $params
	 * @return array|int|string|Result
	 * @throws
	 */
	public function post(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::POST, $path, $params);
	}


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function put(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::PUT, $path, $params);
	}


	/**
	 * @param string $contentType
	 */
	public function setContentType(string $contentType)
	{
		$this->header['Content-Type'] = $contentType;
	}


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function head(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::HEAD, $path, $params);
	}


	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function get(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::GET, $path, $params);
	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function option(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::OPTIONS, $path, $params);
	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function delete(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::DELETE, $path, $params);
	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function options(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::OPTIONS, $path, $params);

	}

	/**
	 * @param string $path
	 * @param array $params
	 * @return array|int|string|Result
	 */
	public function upload(string $path, array $params = []): array|int|string|Result
	{
		return $this->request(self::UPLOAD, $path, $params);
	}


	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * @return int
	 */
	protected function getHostPort(): int
	{
		if (!empty($this->getPort())) {
			return $this->getPort();
		}
		$port = 80;
		if ($this->isSSL()) $port = 443;
		return $port;
	}


	/**
	 * @param string $host
	 */
	public function setHost(string $host): void
	{
		$this->host = $host;
		if ($this->use_swoole) {
			$this->host = System::gethostbyname($host);
		}
		$this->addHeader('Host', $host);
	}

	/**
	 * @return array
	 */
	public function getHeader(): array
	{
		return $this->header;
	}

	/**
	 * @param array $header
	 */
	public function setHeader(array $header): void
	{
		$this->header = $header;
	}


	/**
	 * @param array $header
	 * @return array
	 */
	public function setHeaders(array $header): array
	{
		if (empty($header)) {
			return [];
		}
		foreach ($header as $key => $val) {
			$this->header[$key] = $val;
		}
		return $this->header;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function addHeader($key, $value)
	{
		$this->header[$key] = $value;
	}

	/**
	 * @return int
	 */
	public function getTimeout(): int
	{
		return $this->timeout;
	}

	/**
	 * @param int $value
	 */
	public function setTimeout(int $value): void
	{
		$this->timeout = $value;
	}

	/**
	 * @return Closure|null
	 */
	public function getCallback(): ?Closure
	{
		return $this->callback;
	}

	/**
	 * @param Closure|null $value
	 */
	public function setCallback(?Closure $value): void
	{
		$this->callback = $value;
	}

	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setMethod(string $value): self
	{
		$this->method = $value;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSSL(): bool
	{
		return $this->isSSL;
	}

	/**
	 * @param bool $isSSL
	 */
	public function setIsSSL(bool $isSSL): void
	{
		$this->isSSL = $isSSL;
	}

	/**
	 * @return string
	 */
	public function getAgent(): string
	{
		return $this->agent;
	}

	/**
	 * @param string $agent
	 */
	public function setAgent(string $agent): void
	{
		$this->agent = $agent;
	}

	/**
	 * @return string
	 */
	public function getErrorCodeField(): string
	{
		return $this->errorCodeField;
	}

	/**
	 * @param string $errorCodeField
	 */
	public function setErrorCodeField(string $errorCodeField): void
	{
		$this->errorCodeField = $errorCodeField;
	}

	/**
	 * @return string
	 */
	public function getErrorMsgField(): string
	{
		return $this->errorMsgField;
	}

	/**
	 * @param string $errorMsgField
	 */
	public function setErrorMsgField(string $errorMsgField): void
	{
		$this->errorMsgField = $errorMsgField;
	}

	/**
	 * @return bool
	 */
	public function isUseSwoole(): bool
	{
		return $this->use_swoole;
	}

	/**
	 * @param bool $use_swoole
	 */
	public function setUseSwoole(bool $use_swoole): void
	{
		$this->use_swoole = $use_swoole;
	}

	/**
	 * @return string
	 */
	public function getSslCertFile(): string
	{
		return $this->ssl_cert_file;
	}

	/**
	 * @param string $ssl_cert_file
	 */
	public function setSslCertFile(string $ssl_cert_file): void
	{
		$this->ssl_cert_file = $ssl_cert_file;
	}

	/**
	 * @return string
	 */
	public function getSslKeyFile(): string
	{
		return $this->ssl_key_file;
	}

	/**
	 * @param string $ssl_key_file
	 */
	public function setSslKeyFile(string $ssl_key_file): void
	{
		$this->ssl_key_file = $ssl_key_file;
	}

	/**
	 * @return string
	 */
	public function getCa(): string
	{
		return $this->ca;
	}

	/**
	 * @param string $ssl_key_file
	 */
	public function setCa(string $ssl_key_file): void
	{
		$this->ca = $ssl_key_file;
	}

	/**
	 * @return int
	 */
	public function getPort(): int
	{
		if ($this->isSSL()) {
			return 443;
		}
		if (empty($this->port)) {
			return 80;
		}
		return $this->port;
	}

	/**
	 * @param int $port
	 */
	public function setPort(int $port): void
	{
		$this->port = $port;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->_message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage(string $message): void
	{
		$this->_message = $message;
	}

	/**
	 * @return string
	 */
	public function getData(): string
	{
		return $this->_data;
	}

	/**
	 * @param string $data
	 */
	public function setData(string $data): void
	{
		$this->_data = $data;
	}

	/**
	 * @return int
	 */
	public function getConnectTimeout(): int
	{
		return $this->connect_timeout;
	}

	/**
	 * @param int $connect_timeout
	 */
	public function setConnectTimeout(int $connect_timeout): void
	{
		$this->connect_timeout = $connect_timeout;
	}


	/**
	 * @param $host
	 * @return string|string[]
	 */
	protected function replaceHost($host): array|string
	{
		if ($this->isHttp($host)) {
			return str_replace('http://', '', $host);
		}
		if ($this->isHttps($host)) {
			return str_replace('https://', '', $host);
		}
		return $host;
	}


	/**
	 * @param $url
	 * @return false|int
	 */
	protected function checkIsIp($url): bool|int
	{
		return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $url);
	}

	/**
	 * @param $url
	 * @return bool
	 */
	protected function isHttp($url): bool
	{
		return str_starts_with($url, 'http://');
	}

	/**
	 * @param $url
	 * @return bool
	 */
	protected function isHttps($url): bool
	{
		return str_starts_with($url, 'https://');
	}


	/**
	 * @param $newData
	 * @return string
	 */
	protected function mergeParams($newData): string
	{
		if (!is_string($newData)) {
			return $this->toRequest($newData);
		}
		return $newData;
	}


	/**
	 * @param $data
	 * @return string
	 */
	protected function toRequest($data): string
	{
		if (is_string($data)) {
			return $data;
		}
		$contentType = 'application/x-www-form-urlencoded';
		if (isset($this->header['Content-Type'])) {
			$contentType = $this->header['Content-Type'];
		} else if (isset($this->header['content-type'])) {
			$contentType = $this->header['content-type'];
		}
		if (str_contains($contentType, 'json')) {
			return Help::toJson($data);
		} else if (str_contains($contentType, 'xml')) {
			return Help::toXml($data);
		} else {
			return http_build_query($data);
		}
	}


	/**
	 * @param $data
	 * @param $body
	 * @return array|string|null
	 */
	protected function resolve($data, $body): array|string|null
	{
		if (is_array($body)) {
			return $body;
		}
		$type = $data['content-type'] ?? $data['Content-Type'] ?? 'text/html';
		if (str_contains($type, 'text/html')) {
			return $body;
		} else if (str_contains($type, 'json')) {
			return json_decode($body, true);
		} else if (str_contains($type, 'xml')) {
			return Help::xmlToArray($body);
		} else if (str_contains($type, 'plain')) {
			return Help::toArray($body);
		}
		return $body;
	}


	/**
	 * @param $body
	 * @param $_data
	 * @param array $header
	 * @param int $statusCode
	 * @return mixed 构建返回体
	 * 构建返回体
	 */
	protected function structure($body, $_data, $header = [], $statusCode = 200): mixed
	{
		if ($this->callback instanceof Closure) {
			$result = call_user_func($this->callback, $body, $_data, $header);
		} else {
			$result = $this->parseResult($body, $header, $statusCode);
		}
		return $result;
	}


	/**
	 * @param $body
	 * @param $header
	 * @param $statusCode
	 * @return Result
	 */
	private function parseResult($body, $header, $statusCode): Result
	{
		if (is_string($body)) {
			$result['code'] = 0;
			$result['message'] = '';
		} else {
			$result['code'] = $body[$this->errorCodeField] ?? 0;
			$result['message'] = $this->searchMessageByData($body);
		}
		$result['data'] = $body;
		$result['header'] = $header;
		$result['httpStatus'] = $statusCode;
		return new Result($result);
	}


	/**
	 * @param $body
	 * @return mixed
	 */
	protected function searchMessageByData($body): mixed
	{
		$parent = [];
		if (empty($this->errorMsgField)) {
			return 'system success.';
		}
		$explode = explode('.', $this->errorMsgField);
		if (!isset($body[$explode[0]])) {
			return 'system success.';
		}
		foreach ($explode as $item) {
			if (empty($item)) {
				continue;
			}
			if (empty($parent)) {
				$parent = $body[$item];
				continue;
			}
			if (is_string($parent) || !isset($parent[$item])) {
				break;
			}
			$parent = $parent[$item];
		}
		return !empty($parent) ? $parent : 'system success.';
	}


	/**
	 * @return bool
	 * check isPost Request
	 */
	public function isPost(): bool
	{
		return strtolower($this->method) === self::POST;
	}


	/**
	 * @return bool
	 *
	 * check isGet Request
	 */
	public function isGet(): bool
	{
		return strtolower($this->method) === self::GET;
	}

	/**
	 * @param        $arr
	 *
	 * @return array|string
	 * 将请求参数进行编码
	 */
	protected function paramEncode($arr): array|string
	{
		if (!is_array($arr)) {
			return $arr;
		}
		$_tmp = [];
		foreach ($arr as $Key => $val) {
			$_tmp[$Key] = $val;
		}
		if ($this->isGet()) {
			return http_build_query($_tmp);
		}
		return $_tmp;
	}


	/**
	 * @param string $string
	 * @return array
	 */
	protected function matchHost(string $string): array
	{
		if (($parse = isUrl($string, true)) === false) {
			return $this->defaultString($string);
		}
		[$isHttps, $domain, $port, $path] = $parse;
		if (str_contains($domain, ':' . $port)) {
			$domain = str_replace(':' . $port, '', $domain);
		}
		$this->port = $isHttps ? 443 : $this->port;
		if (isIp($domain)) {
			$this->host = $domain;
		} else if ($this->isUseSwoole()) {
			$this->host = System::gethostbyname($domain) ?? $domain;
		} else {
			$this->host = $domain;
		}
		$this->header['Host'] = $domain;
		if (strpos($path, '/') !== 0) {
			$path = '/' . $path;
		}
		return [$this->host, $isHttps, $path];
	}


	/**
	 * @param $string
	 * @return array
	 */
	private function defaultString($string): array
	{
		$host = $this->getHost();
		if ($string == '/') {
			$string = '';
		} else if (strpos($string, '/') !== 0) {
			$string = '/' . $string;
		}
		return [$host, $this->isSSL(), $string];
	}


	/**
	 * @param $path
	 * @param $params
	 * @return string
	 */
	protected function joinGetParams($path, $params): string
	{
		if (empty($params)) {
			return $path;
		}
		if (!is_string($params)) {
			$params = http_build_query($params);
		}
		if (str_contains($path, '?')) {
			[$path, $getParams] = explode('?', $path);
		}
		if (!isset($getParams) || empty($getParams)) {
			return $path . '?' . $params;
		}
		return $path . '?' . $params . '&' . $getParams;
	}


	/**
	 * @param $code
	 * @param $message
	 * @param $data
	 * @param $header
	 * @return Result
	 */
	protected function fail($code, $message, $data = [], $header = []): Result
	{
		return new Result([
			'code'    => $code,
			'message' => $message,
			'data'    => $data,
			'header'  => $header,
		]);
	}


}
