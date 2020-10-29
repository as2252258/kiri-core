<?php
declare(strict_types=1);

namespace HttpServer\Client;


use Closure;
use Exception;
use Snowflake\Core\Help;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Coroutine\System;

/**
 * Class Curl
 * @package HttpServer\Client
 */
class Curl
{

	const POST = 'post';
	const GET = 'get';
	const DELETE = 'delete';
	const OPTIONS = 'options';
	const HEAD = 'head';
	const PUT = 'put';


	private array $curl_multi = [];

	private array $headers = [
		'Connection' => 'Keep-Alive',
		'Keep-Alive' => '300'
	];

	/** @var ?Closure */
	private ?\Closure $callback;

	/** @var string */
	private string $errorCodeField = '';

	/** @var string */
	private string $errorMsgField = '';

	/** @var int */
	private int $timeout = -1;

	/** @var int */
	private int $connection_timeout = 2;

	/** @var bool */
	private bool $useKeepAlive = true;

	/** @var string */
	private string $agent = '';

	/** @var string */
	private string $ssl_key = '';

	/** @var string */
	private string $ssl_cert = '';

	/** @var string */
	private string $ssl_ca = '';

	/** @var string */
	private string $host = '127.0.0.1';

	/** @var int */
	private int $port = 9958;

	/** @var bool */
	private bool $isSsl = true;


	/** @var Curl */
	private static Curl $instance;

	/**
	 * @return array|Closure
	 */
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	 * @param Closure $callback
	 */
	public function setCallback(Closure $callback): void
	{
		$this->callback = $callback;
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
	 * @return int
	 */
	public function getTimeout(): int
	{
		return $this->timeout;
	}

	/**
	 * @param int $timeout
	 */
	public function setTimeout(int $timeout): void
	{
		$this->timeout = $timeout;
	}

	/**
	 * @return int
	 */
	public function getConnectionTimeout(): int
	{
		return $this->connection_timeout;
	}

	/**
	 * @param int $connection_timeout
	 */
	public function setConnectionTimeout(int $connection_timeout): void
	{
		$this->connection_timeout = $connection_timeout;
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
	public function getSslKey(): string
	{
		return $this->ssl_key;
	}

	/**
	 * @param string $ssl_key
	 * @throws Exception
	 */
	public function setSslKey(string $ssl_key): void
	{
		$ssl_key = realpath($ssl_key);
		if (!file_exists($ssl_key)) {
			throw new Exception('Ssl file not exists.');
		}
		$this->ssl_key = $ssl_key;
	}

	/**
	 * @return string
	 */
	public function getSslCert(): string
	{
		return $this->ssl_cert;
	}

	/**
	 * @param string $ssl_cert
	 * @throws Exception
	 */
	public function setSslCert(string $ssl_cert): void
	{
		$ssl_cert = realpath($ssl_cert);
		if (!file_exists($ssl_cert)) {
			throw new Exception('Ssl file not exists.');
		}
		$this->ssl_cert = $ssl_cert;
	}

	/**
	 * @return string
	 */
	public function getSslCa(): string
	{
		return $this->ssl_ca;
	}

	/**
	 * @param string $ssl_ca
	 * @throws Exception
	 */
	public function setSslCa(string $ssl_ca): void
	{
		$ssl_ca = realpath($ssl_ca);
		if (!file_exists($ssl_ca)) {
			throw new Exception('Ssl file not exists.');
		}
		$this->ssl_ca = $ssl_ca;
	}


	/**
	 * @return string[]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * @param string[] $headers
	 */
	public function setHeaders(array $headers): void
	{
		$this->headers = $headers;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function setHeader($key, $value): void
	{
		$this->headers[$key] = $value;
	}


	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->isSsl;
	}

	/**
	 * @param bool $isSsl
	 */
	public function setIsSsl(bool $isSsl): void
	{
		$this->isSsl = $isSsl;
		if ($this->isSsl()) {
			$this->port = 443;
		}
	}


	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * @param string $host
	 */
	public function setHost(string $host): void
	{
		if (!preg_match('/(\d{1,3}\.){4}/', $host . '.')) {
			$this->setHeader('Host', $host);
			$this->host = System::gethostbyname($host);
		} else {
			$this->host = $host;
		}
	}

	/**
	 * @return string
	 */
	public function getPort(): int
	{
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
	 * @param int $keepLive
	 */
	public function setKeepAlive(int $keepLive)
	{
		if ($keepLive < 0) {
			$keepLive = 300;
		}
		$this->headers['Keep-Alive'] = $keepLive;
	}

	/**
	 * @return bool
	 */
	public function isUseKeepAlive(): bool
	{
		return $this->useKeepAlive;
	}

	/**
	 * @param bool $useKeepAlive
	 */
	public function setUseKeepAlive(bool $useKeepAlive): void
	{
		$this->useKeepAlive = $useKeepAlive;
	}

	/**
	 * @return Curl
	 */
	public static function NewRequest()
	{
		if (!(static::$instance instanceof Curl)) {
			static::$instance = new Curl();
		}
		static::$instance->setHeaders([]);
		static::$instance->callback = null;
		return static::$instance;
	}


	/**
	 * @param $path
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function get($path, $params = [])
	{
		return $this->request($this->joinGetParams($path, $params), self::GET);
	}


	/**
	 * @param $path
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function post($path, $params = [])
	{
		return $this->request($path, self::POST, $params);
	}

	/**
	 * @param $path
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function delete($path, $params = [])
	{
		return $this->request($path, self::DELETE, $params);
	}

	/**
	 * @param $path
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function put($path, $params = [])
	{
		return $this->request($path, self::PUT, $params);
	}

	/**
	 * @param $path
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function options($path, $params = [])
	{
		return $this->request($path, self::OPTIONS, $params);
	}


	/**
	 * @param $path
	 * @param $method
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	private function request($path, $method, $params = [])
	{
		return $this->execute($this->getCurlHandler($path, $method, $params));
	}


	/**
	 * @param $path
	 * @param array $methods
	 */
	public function clean($path, array $methods)
	{
		[$host, $isHttps, $path] = $this->matchHost($path);
		foreach ($methods as $method) {
			$hash = hash('sha256', $host . $path . $method);
			if (!isset($this->curl_multi[$hash])) {
				continue;
			}
			unset($this->curl_multi[$hash]);
		}
	}


	/**
	 * @param $path
	 * @param $method
	 * @param $params
	 * @return mixed|resource
	 * @throws Exception
	 */
	private function getCurlHandler($path, $method, $params)
	{
		[$host, $isHttps, $path] = $this->matchHost($path);

		$hash = hash('sha256', $host . $path . $method);
		if (isset($this->curl_multi[$hash])) {
			return $this->curl_multi[$hash];
		}

		$resource = $this->do(curl_init($host . $path), $host . $path, $method);
		if ($method === self::POST && !empty($params)) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, HttpParse::parse($params));
		}

		if ($isHttps !== false) {
			return $this->curl_multi[$hash] = $this->curlHandlerSslSet($resource);
		}
		return $this->curl_multi[$hash] = $resource;
	}


	/**
	 * @param $path
	 * @param $params
	 * @return mixed|resource
	 * @throws Exception
	 */
	public function upload($path, $params)
	{
		[$host, $isHttps, $path] = $this->matchHost($path);
		$resource = $this->do(curl_init($host . $path), $host . $path, self::POST);

		@curl_setopt($resource, CURLOPT_POSTFIELDS, $params);

		if ($isHttps !== false) {
			return $this->execute($this->curlHandlerSslSet($resource));
		}
		return $this->execute($resource);
	}


	/**
	 * @param $resource
	 * @return bool
	 * @throws Exception
	 */
	private function curlHandlerSslSet($resource)
	{
		if (!empty($this->ssl_key)) {
			if (!file_exists($this->ssl_key)) {
				throw new Exception('SSL protocol certificate not found.');
			}
			curl_setopt($resource, CURLOPT_SSLKEY, $this->getSslKey());
		}
		if (!empty($this->ssl_cert)) {
			if (!!file_exists($this->ssl_cert)) {
				throw new Exception('SSL protocol certificate not found.');
			}
			curl_setopt($resource, CURLOPT_SSLCERT, $this->getSslCert());
		}
		return $resource;
	}


	/**
	 * @param $resource
	 * @param $path
	 * @param $method
	 * @return resource
	 * @throws Exception
	 */
	private function do($resource, $path, $method)
	{
		curl_setopt($resource, CURLOPT_TIMEOUT, $this->timeout);                     // 超时设置
		curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);   // 超时设置

		curl_setopt($resource, CURLOPT_HEADER, true);
		curl_setopt($resource, CURLOPT_FAILONERROR, true);

		curl_setopt($resource, CURLOPT_HTTPHEADER, $this->parseHeaderMat());
		curl_setopt($resource, CURLOPT_SSL_FALSESTART, true);
		curl_setopt($resource, CURLOPT_FORBID_REUSE, false);
		curl_setopt($resource, CURLOPT_FRESH_CONNECT, false);

		if (!empty($this->agent)) {
			curl_setopt($resource, CURLOPT_USERAGENT, $this->agent);
		}

		curl_setopt($resource, CURLOPT_NOBODY, FALSE);
		curl_setopt($resource, CURLOPT_RETURNTRANSFER, TRUE);//返回内容
		curl_setopt($resource, CURLOPT_FOLLOWLOCATION, TRUE);// 跟踪重定向
		curl_setopt($resource, CURLOPT_ENCODING, 'gzip,deflate');

		if ($method === self::POST) {
			curl_setopt($resource, CURLOPT_POST, 1);
		}

		curl_setopt($resource, CURLOPT_URL, $path);
		curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $method);

		return $resource;
	}


	/**
	 * @param $path
	 * @param $params
	 * @return string
	 */
	private function joinGetParams($path, $params)
	{
		if (empty($params)) {
			return $path;
		}
		if (!is_string($params)) {
			$params = http_build_query($params);
		}
		if (strpos($path, '?') !== false) {
			[$path, $getParams] = explode('?', $path);
		}
		if (!isset($getParams) || empty($getParams)) {
			return $path . '?' . $params;
		}
		return $path . '?' . $params . '&' . $getParams;
	}


	/**
	 * @param $curl
	 * @return bool|string
	 * @throws Exception
	 */
	private function execute($curl)
	{
		$output = curl_exec($curl);
		if ($output === false) {
			return new Result(['code' => 400, 'message' => curl_error($curl)]);
		}
		if (!$this->isUseKeepAlive()) {
			curl_close($curl);
		}
		return $this->parseResponse($output);
	}


	/**
	 * @param $output
	 * @param $params
	 * @return array|Result|mixed
	 * @throws Exception
	 */
	private function parseResponse($output, $params = [])
	{
		if ($output === FALSE) {
			return new Result(['code' => 500, 'message' => $output]);
		}
		[$header, $body, $status] = $this->explode($output);
		if ($status != 200 && $status != 201) {
			$data = new Result(['code' => $status, 'message' => $body, 'header' => $header]);
		} else {
			$data = $this->structure($body, $params, $header);
		}
		return $data;
	}


	/**
	 * @param $body
	 * @param $_data
	 * @param $header
	 * @param $statusCode
	 * @return array|mixed|Result
	 * 构建返回体
	 */
	private function structure($body, $_data, $header = [], $statusCode = 200)
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
	private function parseResult($body, $header, $statusCode)
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
	 * @return array|mixed|string
	 */
	private function searchMessageByData($body)
	{
		$parent = [];
		if (empty($this->errorMsgField)) {
			return 'Unknown service status.';
		}
		$explode = explode('.', $this->errorMsgField);
		if (!isset($body[$explode[0]])) {
			return 'Unknown service status.';
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
		return !empty($parent) ? $parent : 'Unknown service status.';
	}


	/**
	 * @param $output
	 * @return array
	 * @throws Exception
	 */
	private function explode($output)
	{
		if (empty($output) || strpos($output, "\r\n\r\n") === false) {
			throw new Exception('Get data null.');
		}

		[$header, $body] = explode("\r\n\r\n", $output, 2);
		if ($header == 'HTTP/1.1 100 Continue') {
			[$header, $body] = explode("\r\n\r\n", $body, 2);
		}

		$header = explode("\r\n", $header);

		$status = (int)explode(' ', trim($header[0]))[1];
		$header = $this->headerFormat($header);

		return [$header, $this->resolve($header, $body), $status];
	}

	/**
	 * @param $headers
	 * @return array
	 */
	private function headerFormat($headers)
	{
		$_tmp = [];
		foreach ($headers as $key => $val) {
			$trim = explode(': ', trim($val));

			$_tmp[strtolower($trim[0])] = $trim[1] ?? '';
		}
		return $_tmp;
	}


	/**
	 * @param $data
	 * @param $body
	 * @return mixed
	 */
	private function resolve($data, $body)
	{
		if (is_array($body) || !empty($this->callback)) {
			return $body;
		}
		$type = $data['content-type'] ?? $data['Content-Type'] ?? 'text/html';
		if (strpos($type, 'json') !== false) {
			return json_decode($body, true);
		} else if (strpos($type, 'xml') !== false) {
			return Help::xmlToArray($body);
		} else if (strpos($type, 'plain') !== false) {
			return Help::toArray($body);
		}
		return $body;
	}


	/**
	 * @return array
	 */
	private function parseHeaderMat()
	{
		$headers = [];
		foreach ($this->headers as $key => $val) {
			$headers[$key] = $key . ': ' . $val;
		}
		return array_values($headers);
	}


	/**
	 * @param string $string
	 * @return array|string[]
	 */
	private function matchHost(string $string)
	{
		if (($parse = isUrl($string, true)) === false) {
			return $this->defaultString($string);
		}
		[$isHttps, $domain, $port, $path] = $parse;
		if (strpos($domain, ':' . $port) !== false) {
			$domain = str_replace(':' . $port, '', $domain);
		}
		if (isIp($domain)) {
			$this->host = $domain;
		} else {
			$this->host = System::gethostbyname($domain) ?? $domain;
		}

		if (!empty($this->port)) {
			$port = $this->port;
		}
		if (!empty($port) && $port != 443) {
			$this->host .= ':' . $port;
		}

		$this->headers['Host'] = $domain;
		if (strpos($path, '/') !== 0) {
			$path = '/' . $path;
		}
		return [$this->host, $isHttps, $path];
	}


	/**
	 * @param $string
	 * @return array
	 */
	private function defaultString($string)
	{
		$host = $this->getHost();
		if (!empty($this->port) && $this->port != 443) {
			$host .= ':' . $this->getPort();
		}
		if ($string == '/') {
			$string = '';
		} else if (strpos($string, '/') !== 0) {
			$string = '/' . $string;
		}
		return [$host, false, $string];
	}

}
