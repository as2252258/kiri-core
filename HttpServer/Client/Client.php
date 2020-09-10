<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:34
 */

namespace HttpServer\Client;


use Snowflake\Core\Help;
use Exception;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client as SClient;
use Swoole\Coroutine\System;

/**
 * Class Client
 * @package Snowflake\Snowflake\Http
 */
class Client
{
	private $host = '';

	private $header = [];

	private $timeout = 0;

	private $callback = null;
	private $method = 'get';

	private $isSSL = false;
	private $agent = '';
	private $errorCodeField = '';
	private $errorMsgField = '';
	private $use_swoole = false;

	private $ssl_cert_file = '';
	private $ssl_key_file = '';
	private $ca = '';
	private $port = '';

	/** @var string $_message 错误信息 */
	private $_message = '';
	private $_data = '';

	private $connect_timeout = 1;

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
	 * @return string
	 */
	public function getCa(): string
	{
		return $this->ca;
	}

	/**
	 * @param string $ca
	 */
	public function setCa(string $ca): void
	{
		$this->ca = $ca;
	}


	/**
	 * @return string
	 */
	public function getPort(): string
	{
		return $this->port;
	}

	/**
	 * @param string $port
	 */
	public function setPort(string $port): void
	{
		$this->port = $port;
	}

	const POST = 'post';
	const GET = 'get';
	const PUT = 'put';
	const DELETE = 'delete';
	const OPTIONS = 'option';

	/**
	 * HttpClient constructor.
	 */
	private function __construct()
	{
	}

	/**
	 * @param $data
	 */
	public function setData($data)
	{
		$this->_data = $data;
	}

	/**
	 * @return string
	 */
	public function getSslCertFile(): string
	{
		return $this->ssl_cert_file;
	}

	/**
	 * @return string
	 */
	public function hasSslCertFile(): string
	{
		return !empty($this->ssl_cert_file) && file_exists($this->ssl_cert_file);
	}

	/**
	 * @return string
	 */
	public function hasSslKeyFile(): string
	{
		return !empty($this->ssl_key_file) && file_exists($this->ssl_key_file);
	}

	/**
	 * @param string $ssl_cert_file
	 */
	public function setSslCertFile(string $ssl_cert_file)
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
	public function setSslKeyFile(string $ssl_key_file)
	{
		$this->ssl_key_file = $ssl_key_file;
	}

	/**
	 */
	public static function NewRequest()
	{
		return new Client();
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setErrorField(string $name)
	{
		$this->errorCodeField = $name;
		return $this;
	}

	/**
	 * @param $bool
	 * @return $this
	 */
	public function setUseSwoole($bool)
	{
		$this->use_swoole = $bool;
		if ($this->use_swoole) {
			function_exists('setCli') && setCli(true);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setErrorMsgField(string $name)
	{
		$this->errorMsgField = $name;
		return $this;
	}

	/**
	 * @param string $host
	 */
	public function setHost(string $host)
	{
		$this->host = $this->replaceHost($host);
		$match_quest = '/^[a-zA-Z\-]+(\.[a-zA-Z\-])+/';
		if (preg_match($match_quest, $this->host)) {
			$this->addHeader('Host', $this->host);
		}
	}


	/**
	 * @param $path
	 * @param array $data
	 * @param int $type
	 * @return Result
	 */
	public function sendTo($path, array $data, $type = SWOOLE_TCP)
	{
		$client = new \Swoole\Coroutine\Client($type);
		if (empty($this->host) || empty($this->port)) {
			return new Result(['code' => 500, 'message' => 'Host and port is null']);
		}
		if (!$client->connect($this->host, $this->port, $this->connect_timeout)) {
			return new Result(['code' => 500, 'message' => $client->errMsg]);
		}

		$path = '/' . $this->port . '/' . ltrim($path, '/');

		$params['body'] = $data;
		$params['path'] = $path;
		$params['header']['request_uri'] = $path;
		$params['header']['request_method'] = 'receive';

		if ($client->send(serialize($params))) {
			$recv = $this->timeout > 0 ? $client->recv($this->timeout) : $client->recv();
			$param = $this->structure(Help::toArray($recv), $data, null, 200);
		} else {
			$param = new Result(['code' => 500, 'message' => $client->errMsg]);
		}
		$client->close();
		return $param;

	}

	/**
	 * @param int $sec
	 * 设置超时时间
	 */
	public function setTimeout(int $sec)
	{
		$this->timeout = $sec;
	}


	/**
	 * @param $key
	 * @param $value
	 */
	public function setHeader($key, $value)
	{
		$this->header[$key] = $value;
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
	 * @param null $callback
	 */
	public function setCallback($callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @param string $method
	 */
	public function setMethod(string $method)
	{
		$this->method = $method;
	}

	/**
	 * @param string $agent
	 */
	public function setAgent(string $agent)
	{
		$this->agent = $agent;
	}

	/**
	 * @param bool $isSSL
	 */
	public function setIsSSL(bool $isSSL)
	{
		$this->isSSL = $isSSL;
		if ($this->isSSL) {
			$this->port = 443;
		}
	}

	/**
	 * @return bool
	 */
	public function getIsSSL()
	{
		return $this->isSSL;
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 */
	private function request($url, $data = [])
	{
		$data = $this->paramEncode($data);
		if ($this->use_swoole) {
			return $this->coroutine($this->matchHost($url), $data);
		} else {
			return $this->useCurl($url, $data);
		}
	}

	/**
	 * @return bool
	 */
	private function isCli()
	{
		return function_exists('getIsCli') && getIsCli();
	}

	/**
	 * @param string $string
	 * @return bool|string
	 * @throws Exception
	 */
	private function matchHost($string = '')
	{
		if (empty($string)) {
			return false;
		}

		if ($this->isHttp($string)) {
			$string = str_replace('http://', '', $string);
			$hostAndUrls = explode('/', $string);

			$this->host = array_shift($hostAndUrls);
			$string = implode('/', $hostAndUrls);
		} else if ($this->isHttps($string)) {
			$string = str_replace('https://', '', $string);
			$this->setIsSSL(true);

			$hostAndUrls = explode('/', $string);

			$this->host = array_shift($hostAndUrls);
			$string = implode('/', $hostAndUrls);
		} else if (empty($this->host)) {
			$hostAndUrls = explode('/', $string);
			$this->host = array_shift($hostAndUrls);

			$string = implode('/', $hostAndUrls);
		}

		if (strpos($this->host, ':') !== false) {
			[$this->host, $this->port] = explode(':', $this->host);
		}

		if (!$this->checkIsIp($this->host) && Coroutine::getuid() > 0) {
			$this->host = System::gethostbyname($this->host);
		}

		if (!$this->checkIsIp($this->host) && !$this->isDomainName($this->host)) {
			throw new Exception('Client Host error.');
		}

		return $string;
	}

	/**
	 * @param $name
	 * @return bool|mixed
	 */
	private function isDomainName($name)
	{
		if (!preg_match('/^[a-zA-Z\-0-9]+(\.[a-zA-Z\-0-9]+)+[^\/]?/', $name, $out)) {
			return false;
		}
		return $out[0];
	}

	/**
	 * @param $url
	 * @param $data
	 * @return array|Result|mixed
	 * @throws
	 */
	private function useCurl($url, $data)
	{
		if ($this->isHttp($url) || $this->isHttps($url)) {
			return $this->curl($url, $data);
		}
		$url = $this->matchHost(ltrim($url, '/'));
		if (!empty($this->port)) {
			$this->host .= ':' . $this->port;
		}
		if ($this->isSSL) {
			return $this->curl('https://' . $this->host . '/' . $url, $data);
		} else {
			return $this->curl('http://' . $this->host . '/' . $url, $data);
		}
	}

	/**
	 * @param $host
	 * @return string|string[]
	 */
	private function replaceHost($host)
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
	private function checkIsIp($url)
	{
		return preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $url);
	}

	/**
	 * @param $url
	 * @return bool
	 */
	private function isHttp($url)
	{
		return strpos($url, 'http://') === 0;
	}

	/**
	 * @param $url
	 * @return bool
	 */
	private function isHttps($url)
	{
		return strpos($url, 'https://') === 0;
	}


	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 * 使用swoole协程方式请求
	 */
	private function coroutine($url, $data = [])
	{
		try {
			$client = $this->generate_client($this->host, $url, $data);
			if ($client->statusCode < 0) {
				throw new Exception($client->errMsg);
			}
			unset($this->_data);

			$body = $this->resolve($client->getHeaders(), $client->body);
			if (!in_array($client->getStatusCode(), [200, 201])) {
				if (is_string($body)) {
					$message = 'Request error code ' . $client->getStatusCode();
				} else {
					$message = $this->searchMessageByData($body);
				}
				$response['code'] = $client->getStatusCode();
				$response['message'] = $message;
				$response['data'] = $body;
				$response['header'] = $client->getHeaders();

				$response = new Result($response);
			} else {
				$response = $this->structure($body, $data, $client->getHeaders());
			}
		} catch (\Throwable $exception) {
			$response['code'] = 500;
			$response['message'] = $exception->getMessage();
			$response['data'] = array_slice($exception->getTrace(), 0, 6);
			$response['header'] = [];

			$response = new Result($response);
		}
		return $response;
	}

	/**
	 * @return int
	 */
	private function getHostPort()
	{
		if (!empty($this->port)) {
			return $this->port;
		}
		$port = 80;
		if ($this->isSSL) $port = 443;
		return $port;
	}

	/**
	 * @param $host
	 * @param $url
	 * @param $data
	 * @return SClient
	 */
	private function generate_client($host, $url, $data = [])
	{
		$client = new SClient($host, $this->getHostPort(), $this->isSSL);
		if (strpos($url, '/') !== 0) {
			$url = '/' . $url;
		}

		$client->set($this->settings());
		if (!empty($this->agent)) {
			$this->header['User-Agent'] = $this->agent;
		}
		if (!empty($this->header)) {
			$client->setHeaders($this->header);
		}
		$client->setMethod(strtoupper($this->method));
		if (strtolower($this->method) == self::GET && !empty($data)) {
			$url .= '?' . $data;
		} else {
			$this->_data = $this->mergeParams($data);
		}

		if (!empty($this->_data)) {
			$client->setData($this->_data);
		}
		$client->execute($url);
		$client->close();
		return $client;
	}

	/**
	 * @param $newData
	 * @return mixed
	 */
	private function mergeParams($newData)
	{
		if (empty($this->_data)) {
			return $this->toRequest($newData);
		} else if (empty($newData)) {
			return $this->toRequest($this->_data);
		}

		$newData = Help::toArray($newData);
		$array = Help::toArray($this->_data);

		$params = array_merge($array, $newData);

		return $this->toRequest($params);
	}


	/**
	 * @param $data
	 * @return false|mixed|string
	 */
	private function toRequest($data)
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

		if (strpos($contentType, 'json') !== false) {
			return Help::toJson($data);
		} else if (strpos($contentType, 'xml') !== false) {
			return Help::toXml($data);
		} else {
			return http_build_query($data);
		}
	}

	/**
	 * @return array
	 */
	private function settings()
	{
		$sslCert = $this->getSslCertFile();
		$sslKey = $this->getSslKeyFile();
		$sslCa = $this->getCa();

		$params = [];
		if ($this->connect_timeout > 0) {
			$params['timeout'] = $this->connect_timeout;
		}
		if (empty($sslCert) || empty($sslKey) || empty($sslCa)) {
			return $params;
		}

		$params['ssl_host_name'] = $this->host;
		$params['ssl_cert_file'] = $this->getSslCertFile();
		$params['ssl_key_file'] = $this->getSslKeyFile();
		$params['ssl_verify_peer'] = true;
		$params['ssl_cafile'] = $sslCa;

		return $params;
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 */
	private function curl($url, $data = [])
	{
		try {
			$output = $this->curlParse($url, $this->mergeParams($data));
			if ($output === FALSE) {
				return new Result(['code' => 500, 'message' => $output]);
			}
			[$header, $body, $status] = $this->explode($output);
			if (!in_array($status, [200, 201])) {
				$data = new Result(['code' => $status, 'message' => $body, 'header' => $header]);
			} else {
				$data = $this->structure($body, $data, $header);
			}
			return $data;
		} catch (\Throwable $exception) {
			$response['code'] = 500;
			$response['message'] = $exception->getMessage();
			$response['data'] = array_slice($exception->getTrace(), 0, 6);
			$response['header'] = [];
			return new Result($response);
		}
	}

	/**
	 * @param $url
	 * @param $data
	 * @return bool|string
	 * @throws Exception
	 */
	private function curlParse($url, $data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->createRequestUrl($url, $data));
		if ($this->timeout > 0) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);          // 超时设置
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);   // 超时设置
		}
		curl_setopt($ch, CURLOPT_HEADER, true);

		if ($headers = $this->parseHeaderMat()) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if (!empty($this->agent)) {
			curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		}
		if (file_exists($cert = $this->getSslCertFile())) {
			curl_setopt($ch, CURLOPT_SSLCERT, $cert);
		}
		if (file_exists($key = $this->getSslKeyFile())) {
			curl_setopt($ch, CURLOPT_SSLKEY, $key);
		}

		curl_setopt($ch, CURLOPT_NOBODY, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);//返回内容
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);// 跟踪重定向
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

		if ($this->method == self::POST) {
			curl_setopt($ch, CURLOPT_POST, 1);
		}

		if ($this->method != self::GET) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
		$output = curl_exec($ch);
		if ($output === false) {
			throw new Exception(curl_error($ch));
		}
		curl_close($ch);
		return $output;
	}


	/**
	 * @param $url
	 * @param $params
	 * @return array|mixed|Result
	 * 上传文件
	 */
	public function upload($url, $params)
	{
		try {
			$this->method = self::POST;
			$output = $this->curlParse($url, $params);
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
		} catch (\Throwable $exception) {
			$response['code'] = 500;
			$response['message'] = $exception->getMessage();
			$response['data'] = array_slice($exception->getTrace(), 0, 6);
			$response['header'] = [];
			return new Result($response);
		}
	}


	/**
	 * @param $output
	 * @return array
	 */
	private function explode($output)
	{
		[$header, $body] = explode("\r\n\r\n", $output, 2);
		if ($header == 'HTTP/1.1 100 Continue') {
			[$header, $body] = explode("\r\n\r\n", $body, 2);
		} else if (strpos($body, "\r\n\r\n") !== false) {
			[$header, $body] = explode("\r\n\r\n", $body, 2);
		}
		$header = explode("\r\n", $header);

		unset($output);

		$status = (int)explode(' ', trim($header[0]))[1];
		$header = $this->headerFormat($header);

		return [$header, $this->resolve($header, $body), $status];
	}

	/**
	 * @param $url
	 * @param $data
	 * @return string
	 */
	private function createRequestUrl($url, $data)
	{
		if ($this->isGet()) {
			return $url . '?' . $data;
		}
		return $url;
	}

	/**
	 * @param $data
	 * @param $body
	 * @return mixed
	 */
	private function resolve($data, $body)
	{
		if (is_array($body)) {
			return $body;
		}
		$type = $data['content-type'] ?? $data['Content-Type'] ?? 'text/html';
		if (strpos($type, 'text/html') !== false) {
			return $body;
		} else if (strpos($type, 'json') !== false) {
			return json_decode($body, true);
		} else if (strpos($type, 'xml') !== false) {
			return Help::xmlToArray($body);
		} else if (strpos($type, 'plain') !== false) {
			return Help::toArray($body);
		}
		return $body;
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
	 * @param $body
	 * @param $_data
	 * @param $header
	 * @param $statusCode
	 * @return array|mixed|Result
	 * 构建返回体
	 */
	private function structure($body, $_data, $header = [], $statusCode = 200)
	{
		$this->setIsSSL(false);
		$this->setHeaders([]);

		if ($this->callback !== NULL) {
			$result = call_user_func($this->callback, $body, $_data, $header);
			$this->setCallback(null);

			return $result;
		}
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
	public function isPost()
	{
		return strtolower($this->method) === self::POST;
	}

	/**
	 * @return bool
	 *
	 * check isGet Request
	 */
	public function isGet()
	{
		return strtolower($this->method) === self::GET;
	}

	/**
	 * @param        $arr
	 *
	 * @return array|string
	 * 将请求参数进行编码
	 */
	private function paramEncode($arr)
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
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws
	 */
	public function post($url, $data = [])
	{
		$this->setMethod(self::POST);
		return $this->request($url, $data);
	}


	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws
	 */
	public function put($url, $data = [])
	{
		$this->setMethod(self::PUT);
		return $this->request($url, $data);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws
	 */
	public function get($url, $data = [])
	{
		$this->setMethod(self::GET);
		return $this->request($url, $data);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 */
	public function option($url, $data = [])
	{
		$this->setMethod(self::OPTIONS);
		return $this->request($url, $data);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 */
	public function delete($url, $data = [])
	{
		$this->setMethod(self::DELETE);
		return $this->request($url, $data);
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 */
	public function send($url, $data = [])
	{
		return $this->request($url, $data);
	}

	/**
	 * @return array
	 */
	private function parseHeaderMat()
	{
		if ($this->use_swoole) {
			return $this->header;
		}
		$headers = [];
		foreach ($this->header as $key => $val) {
			$header = $key . ':' . $val;
			if (in_array($header, $headers)) {
				continue;
			}
			$headers[] = $header;
		}
		$this->header = [];
		return $headers;
	}

	/**
	 * @param array $headers
	 * @return array
	 */
	public function setHeaders(array $headers)
	{
		if (empty($headers)) {
			return [];
		}
		foreach ($headers as $key => $val) {
			$this->header[$key] = $val;
		}
		return $this->header;
	}
}
