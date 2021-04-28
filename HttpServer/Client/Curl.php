<?php
declare(strict_types=1);

namespace HttpServer\Client;


use Exception;
use JetBrains\PhpStorm\Pure;


/**
 * Class Curl
 * @package HttpServer\Client
 */
class Curl extends ClientAbstracts
{

	/**
	 * @param $method
	 * @param $path
	 * @param array $params
	 * @return Result|bool|array|string
	 * @throws Exception
	 */
	public function request($method, $path, $params = []): Result|bool|array|string
	{
		if ($method == self::GET) {
			$path = $this->joinGetParams($path, $params);
		}
		return $this->execute($this->getCurlHandler($path, $method, $params));
	}


	/**
	 * @param $path
	 * @param $method
	 * @param $params
	 * @return mixed
	 * @throws Exception
	 */
	private function getCurlHandler($path, $method, $params): mixed
	{
		[$host, $isHttps, $path] = $this->matchHost($path);

		$host = $isHttps ? 'https://' . $host : 'http://' . $host;
		if ($this->getPort() != 443 && $this->getPort() != 80) {
			$host .= ':' . $this->getPort();
		}

		$resource = $this->do(curl_init($host . $path), $host . $path, $method);
		if ($isHttps !== false) {
			$this->curlHandlerSslSet($resource);
		}
		if (empty($params) && empty($this->getData())) {
			return $resource;
		}

		if (!empty($this->getData())) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, $this->getData());
		} else if ($method === self::POST) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, $this->mergeParams($params));
		} else if ($method === self::UPLOAD) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, $params);
		}
		return $resource;
	}


	/**
	 * @param $resource
	 * @return bool
	 * @throws Exception
	 */
	private function curlHandlerSslSet($resource): mixed
	{
		if (!empty($this->ssl_key)) {
			if (!file_exists($this->ssl_key)) {
				throw new Exception('SSL protocol certificate not found.');
			}
			curl_setopt($resource, CURLOPT_SSLKEY, $this->getSslKeyFile());
		}
		if (!empty($this->ssl_cert)) {
			if (!!file_exists($this->ssl_cert)) {
				throw new Exception('SSL protocol certificate not found.');
			}
			curl_setopt($resource, CURLOPT_SSLCERT, $this->getSslCertFile());
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
		curl_setopt($resource, CURLOPT_URL, $path);
		curl_setopt($resource, CURLOPT_TIMEOUT, $this->getTimeout());                     // 超时设置
		curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());       // 超时设置

		curl_setopt($resource, CURLOPT_HEADER, true);
		curl_setopt($resource, CURLOPT_FAILONERROR, true);

		curl_setopt($resource, CURLOPT_HTTPHEADER, $this->parseHeaderMat());
		if (defined('CURLOPT_SSL_FALSESTART')) {
			curl_setopt($resource, CURLOPT_SSL_FALSESTART, true);
		}
		curl_setopt($resource, CURLOPT_FORBID_REUSE, false);
		curl_setopt($resource, CURLOPT_FRESH_CONNECT, false);

		if (!empty($this->getAgent())) {
			curl_setopt($resource, CURLOPT_USERAGENT, $this->getAgent());
		}

		curl_setopt($resource, CURLOPT_NOBODY, FALSE);
		curl_setopt($resource, CURLOPT_RETURNTRANSFER, TRUE);//返回内容
		curl_setopt($resource, CURLOPT_FOLLOWLOCATION, TRUE);// 跟踪重定向
		curl_setopt($resource, CURLOPT_ENCODING, 'gzip,deflate');
		if ($method === self::POST || $method == self::UPLOAD) {
			curl_setopt($resource, CURLOPT_POST, 1);
		}
		curl_setopt($resource, CURLOPT_CUSTOMREQUEST, strtoupper($method));

		return $resource;
	}


	/**
	 * @param $curl
	 * @return Result|bool|array|string
	 * @throws Exception
	 */
	private function execute($curl): Result|bool|array|string
	{
		defer(function () {
			$this->cleanData();
		});
		$output = curl_exec($curl);
		if ($output === false) {
			return $this->fail(400, curl_error($curl));
		}
		return $this->parseResponse($curl, $output);
	}


	/**
	 * @param $curl
	 * @param $output
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	private function parseResponse($curl, $output, $params = []): mixed
	{
		curl_close($curl);
		if ($output === FALSE) {
			return $this->fail(500, $output);
		}
		[$header, $body, $status] = $this->explode($output);
		if ($status != 200 && $status != 201) {
			$data = $this->fail($status, $body, [], $header);
		} else {
			$data = $this->structure($body, $params, $header);
		}
		return $data;
	}


	/**
	 * @param $output
	 * @return array
	 * @throws Exception
	 */
	private function explode($output): array
	{
		if (empty($output) || !str_contains($output, "\r\n\r\n")) {
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
	private function headerFormat($headers): array
	{
		$_tmp = [];
		foreach ($headers as $key => $val) {
			$trim = explode(': ', trim($val));

			$_tmp[strtolower($trim[0])] = $trim[1] ?? '';
		}
		return $_tmp;
	}


	/**
	 * @return array
	 */
	#[Pure] private function parseHeaderMat(): array
	{
		$headers = [];
		foreach ($this->getHeader() as $key => $val) {
			$headers[$key] = $key . ': ' . $val;
		}
		return array_values($headers);
	}
}
