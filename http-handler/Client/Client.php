<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/24 0024
 * Time: 11:34
 */
declare(strict_types=1);

namespace Http\Client;

use Exception;
use JetBrains\PhpStorm\Pure;
use Swoole\Coroutine\Http\Client as SClient;

/**
 * Class Client
 * @package Kiri\Kiri\Http
 */
class Client extends ClientAbstracts
{

	/**
	 * @param string $method
	 * @param $path
	 * @param array $params
	 * @return array|string|Result
	 * @throws Exception
	 */
	public function request(string $method, $path, array $params = []): array|string|Result
	{
		return $this->setMethod($method)
			->coroutine(
				$this->matchHost($path),
				$this->paramEncode($params)
			);
	}


	/**
	 * @param $url
	 * @param array|string $data
	 * @return array|string|Result
	 * @throws Exception 使用swoole协程方式请求
	 */
	private function coroutine($url, array|string $data = []): array|string|Result
	{
		try {
			$client = $this->generate_client($data, ...$url);
			$this->setData('');
			if ($client->statusCode < 0) {
				throw new Exception($client->errMsg);
			}
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
			$this->addError($exception, 'rpc');
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
	private function generate_client($data, $host, $isHttps, $path): SClient
	{
		if ($isHttps || $this->isSSL()) {
			$client = new SClient($host, 443, true);
		} else {
			$client = new SClient($host, $this->getPort(), false);
		}
		$client->set($this->settings());
		if (!empty($this->getAgent())) {
			$this->addHeader('User-Agent', $this->getAgent());
		}
		$client->setHeaders($this->getHeader());
		$client->setMethod(strtoupper($this->getMethod()));
		$client->execute($this->setParams($client, $path, $data));
		$client->close();
		return $client;
	}


	/**
	 * @param SClient $client
	 * @param $path
	 * @param $data
	 * @return string
	 */
	private function setParams(SClient $client, $path, $data): string
	{
		if ($this->isGet()) {
			if (!empty($data)) $path .= '?' . $data;
			if (!empty($this->getData())) {
				$client->setData($this->getData());
			}
		} else {
			if (!empty($this->getData())) {
				$client->setData($this->getData());
			} else {
				$client->setData($this->mergeParams($data));
			}
		}
		return $path;
	}

	/**
	 * @return array
	 */
	#[Pure] private function settings(): array
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