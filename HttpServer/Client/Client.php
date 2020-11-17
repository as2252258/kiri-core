<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:34
 */
declare(strict_types=1);

namespace HttpServer\Client;

use Exception;
use Swoole\Coroutine\Http\Client as SClient;

/**
 * Class Client
 * @package Snowflake\Snowflake\Http
 */
class Client extends ClientAbstracts
{

	/**
	 * @param string $method
	 * @param $url
	 * @param array $data
	 * @return array|mixed|Result
	 * @throws Exception
	 */
	public function request(string $method, $url, $data = [])
	{
		return $this->setMethod($method)
			->coroutine(
				$this->matchHost($url),
				$this->paramEncode($data)
			);
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
			$client = $this->generate_client($data, ...$url);
			if ($client->statusCode < 0) {
				throw new Exception($client->errMsg);
			}
			$this->setData('');
			$body = $this->resolve($client->getHeaders(), $client->body);
			if (in_array($client->getStatusCode(), [200, 201])) {
				return $this->structure($body, $data, $client->getHeaders());
			}
			if (is_string($body)) {
				$message = 'Request error code ' . $client->getStatusCode();
			} else {
				$message = $this->searchMessageByData($body);
			}
			return $this->fail($client->getStatusCode(), $message, $body, $client->getHeaders());
		} catch (\Throwable $exception) {
			return $this->fail(500, $exception->getMessage(), [
				'file' => $exception->getFile(),
				'line' => $exception->getLine()
			], []);
		}
	}

	/**
	 * @param $data
	 * @param $host
	 * @param $isHttps
	 * @param $path
	 * @return SClient
	 */
	private function generate_client($data, $host, $isHttps, $path)
	{
		if ($isHttps || $this->isSSL()) {
			$client = new SClient($host, 443, $this->isSSL());
		} else {
			$client = new SClient($host, $this->getPort(), $this->isSSL());
		}

		if (strpos($path, '/') !== 0) {
			$path = '/' . $path;
		}
		$client->set($this->settings());
		if (!empty($this->getAgent())) {
			$this->addHeader('User-Agent', $this->getAgent());
		}

		$client->setHeaders($this->getHeader());
		$client->setMethod(strtoupper($this->getMethod()));
		if (strtolower($this->getMethod()) == self::GET && !empty($data)) {
			$path .= '?' . $data;
		} else {
			$this->setData($this->mergeParams($data));
		}

		if (!empty($this->getData())) {
			$client->setData($this->getData());
		}
		$client->execute($path);
		$client->close();
		return $client;
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
		if ($this->getConnectTimeout() > 0) {
			$params['timeout'] = $this->getConnectTimeout();
		}
		if (empty($sslCert) || empty($sslKey) || empty($sslCa)) {
			return $params;
		}

		$params['ssl_host_name'] = $this->getHost();
		$params['ssl_cert_file'] = $this->getSslCertFile();
		$params['ssl_key_file'] = $this->getSslKeyFile();
		$params['ssl_verify_peer'] = true;
		$params['ssl_cafile'] = $sslCa;

		return $params;
	}
}
