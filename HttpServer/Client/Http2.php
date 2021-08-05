<?php
declare(strict_types=1);

namespace HttpServer\Client;


use Exception;
use HttpServer\Http\Context;
use Server\Events\OnAfterRequest;
use Snowflake\Abstracts\Component;
use Snowflake\Channel;
use Snowflake\Core\Json;
use Snowflake\Core\Xml;
use Snowflake\Event;
use Snowflake\Events\EventProvider;
use Snowflake\Snowflake;
use Swoole\Coroutine\Http2\Client as H2Client;
use Swoole\Http2\Request;
use Swoole\Http2\Response;


/**
 * Class Http2
 * @package HttpServer\Client
 */
class Http2 extends Component
{


	private array $_clients = [];


	/**
	 * @throws Exception
	 */
	public function init()
	{
		Event::on(Event::SYSTEM_RESOURCE_RELEASES, [$this, 'releases']);
		Event::on(Event::SYSTEM_RESOURCE_CLEAN, [$this, 'clean']);
	}


	/**
	 * @throws Exception
	 */
	public function releases()
	{
		$this->_clients = [];
	}


	/**
	 * 清空
	 */
	public function clean()
	{
		foreach ($this->_clients as $client) {
			/** @var H2Client $client */
			$client->close();
		}
		$this->_clients = [];
	}


	/**
	 * @param bool $isRev
	 * @return Http2
	 */
	public function setIsRev(bool $isRev): static
	{
		Context::setContext('http2isRev', $isRev);
		return $this;
	}


	/**
	 * @param int $timeout
	 * @return Http2
	 */
	public function setTimeout(int $timeout): static
	{
		Context::setContext('http2timeout', $timeout);
		return $this;
	}


	/**
	 * @param array $headers
	 * @return Http2
	 */
	public function setHeader(array $headers): static
	{
		Context::setContext('http2Headers', $headers);
		return $this;
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return Result
	 * @throws Exception
	 */
	public function get($domain, $path, array $params = [], int $timeout = -1): Result
	{
		$request = $this->dispatch($domain, $path, 'GET', $params, $timeout);

		return new Result(['code' => 0, 'data' => $request]);
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return Result
	 * @throws Exception
	 */
	public function post($domain, $path, array $params = [], int $timeout = -1): Result
	{
		$request = $this->dispatch($domain, $path, 'POST', $params, $timeout);

		return new Result(['code' => 0, 'data' => $request]);
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return Result
	 * @throws Exception
	 */
	public function upload($domain, $path, array $params = [], int $timeout = -1): Result
	{
		$request = $this->dispatch($domain, $path, 'POST', $params, $timeout, true);

		return new Result(['code' => 0, 'data' => $request]);
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return Result
	 * @throws Exception
	 */
	public function delete($domain, $path, array $params = [], int $timeout = -1): Result
	{
		$request = $this->dispatch($domain, $path, 'DELETE', $params, $timeout);

		return new Result(['code' => 0, 'data' => $request]);
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param $method
	 * @param array $params
	 * @param int $timeout
	 * @param bool $isUpload
	 * @return mixed
	 * @throws Exception
	 */
	private function dispatch($domain, $path, $method, array $params = [], int $timeout = -1, bool $isUpload = false): mixed
	{
		[$domain, $isSsl] = $this->clear($domain);

		$request = $this->getRequest($path, $method, $params, $isUpload);
		$request->headers = array_merge($request->headers, [
			'Host' => $domain
		]);
		return $this->doRequest($request, $domain, $isSsl, $timeout);
	}


	/**
	 * @param $domain
	 * @return array
	 */
	private function clear($domain): array
	{
		if (str_starts_with($domain, 'https://')) {
			return [str_replace('https://', '', $domain), true];
		} else {
			return [str_replace('http://', '', $domain), false];
		}
	}


	/**
	 * @param Request $request
	 * @param $domain
	 * @param $ssl
	 * @param $timeout
	 * @return mixed
	 * @throws Exception
	 */
	private function doRequest(Request $request, $domain, $ssl, $timeout): mixed
	{
		$client = $this->getClient($domain, $ssl, $timeout);
		$client->send($request);
		if (Context::getContext('http2isRev') === false) {
			return null;
		}
		return $this->rev($client);
	}


	/**
	 * @param $client
	 * @return mixed
	 * @throws Exception
	 */
	private function rev($client): mixed
	{
		/** @var Response $response */
		if (!Context::hasContext('http2timeout')) {
			$response = $client->recv();
		} else {
			$response = $client->recv((int)Context::getContext('http2timeout'));
		}
		if ($response === false || $response->statusCode > 200) {
			throw new Exception($client->errMsg, $client->errCode);
		}
		$header = $response->headers['content-type'];
		if (str_starts_with($header, 'application/json;')) {
			return Json::decode($response->data);
		} else if (str_starts_with($header, 'application/xml;')) {
			return Xml::toArray($response->data);
		} else {
			return $response->data;
		}
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return mixed
	 * @throws Exception
	 */
	public function put($domain, $path, array $params = [], int $timeout = -1): Result
	{
		$request = $this->dispatch($domain, $path, 'PUT', $params, $timeout);

		return new Result(['code' => 0, 'data' => $request]);
	}


	/**
	 * @param $path
	 * @param $method
	 * @param $params
	 * @param bool $isUpload
	 * @return Request
	 * @throws Exception
	 */
	public function getRequest($path, $method, $params, bool $isUpload = false): Request
	{
		if (!str_starts_with($path, '/')) {
			$path = '/' . $path;
		}

		$request = new Request();
		$request->method = $method;
		$request->path = $path;
		if ($method === 'GET') {
			$request->path .= '?' . http_build_query($params);
		} else {
			$request->data = !is_string($params) && !$isUpload ? Json::encode($params) : $params;
		}
		$request->headers = Context::getContext('http2Headers');
		return $request;
	}


	/**
	 * @param $domain
	 * @param bool $isSsl
	 * @param int $timeout
	 * @return H2Client
	 * @throws Exception
	 */
	private function getClient($domain, bool $isSsl = false, int $timeout = -1): H2Client
	{
		if (isset($this->_clients[$domain])) {
			return $this->_clients[$domain];
		}
		$client = $this->newRequest($domain, $isSsl, $timeout);
		if ((!$client->connected || !$client->ping()) && !$client->connect()) {
			throw new Exception($client->errMsg, $client->errCode);
		}
		return $this->_clients[$domain] = $client;
	}


	/**
	 * @param $domain
	 * @param $isSsl
	 * @param $timeout
	 * @return H2Client
	 */
	public function newRequest($domain, $isSsl, $timeout): H2Client
	{
		$domain = rtrim($domain, '/');
		if (str_contains($domain, ':')) {
			[$domain, $port] = explode(':', $domain);
		} else {
			$port = $isSsl === true ? 443 : 80;
		}
		$client = new H2Client($domain, (int)$port, $isSsl);
		$client->set(['timeout' => $timeout, 'ssl_host_name' => $domain]);
		return $client;
	}


}
