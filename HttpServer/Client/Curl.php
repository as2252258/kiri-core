<?php
declare(strict_types=1);

namespace HttpServer\Client;


use Exception;

/**
 * Class Curl
 * @package HttpServer\Client
 */
class Curl extends ClientAbstracts
{

	/**
	 * @param $path
	 * @param $method
	 * @param array $params
	 * @return bool|string
	 * @throws Exception
	 */
	public function request($method, $path, $params = [])
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
	 * @return mixed|resource
	 * @throws Exception
	 */
	private function getCurlHandler($path, $method, $params)
	{
		[$host, $isHttps, $path] = $this->matchHost($path);

		$resource = $this->do(curl_init($host . $path), $host . $path, $method);
		if ($method === self::POST && !empty($params)) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, HttpParse::parse($params));
		} else if ($method === self::UPLOAD) {
			curl_setopt($resource, CURLOPT_POSTFIELDS, $params);
		}

		if ($isHttps !== false) {
			return $this->curlHandlerSslSet($resource);
		}
		return $resource;
	}


	/**
	 * @param $path
	 * @param $params
	 * @return mixed|resource
	 * @throws Exception
	 */
	public function upload($path, $params = [])
	{
		[$host, $isHttps, $path] = $this->matchHost($path);

		$resource = $this->do(curl_init($host . $path), $host . $path, self::POST);

		curl_setopt($resource, CURLOPT_POSTFIELDS, $params);

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
		curl_setopt($resource, CURLOPT_TIMEOUT, $this->getTimeout());                     // 超时设置
		curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());       // 超时设置

		curl_setopt($resource, CURLOPT_HEADER, true);
		curl_setopt($resource, CURLOPT_FAILONERROR, true);

		curl_setopt($resource, CURLOPT_HTTPHEADER, $this->parseHeaderMat());
		curl_setopt($resource, CURLOPT_SSL_FALSESTART, true);
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

		var_dump($path);
		curl_setopt($resource, CURLOPT_URL, $path);
		curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $method);

		return $resource;
	}


	/**
	 * @param $curl
	 * @return bool|string
	 * @throws Exception
	 */
	private function execute($curl)
	{
		$output = curl_exec($curl);
		var_dump($output);
		if ($output === false) {
			return $this->fail(400, curl_error($curl));
		}
		return $this->parseResponse($curl, $output);
	}


	/**
	 * @param $curl
	 * @param $output
	 * @param array $params
	 * @return array|Result|mixed
	 * @throws Exception
	 */
	private function parseResponse($curl, $output, $params = [])
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
	 * @return array
	 */
	private function parseHeaderMat()
	{
		$headers = [];
		foreach ($this->getHeader() as $key => $val) {
			$headers[$key] = $key . ': ' . $val;
		}
		var_dump($headers);
		return array_values($headers);
	}
}
