<?php


namespace HttpServer\Client;


use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Core\JSON;
use Swoole\Http2\Request;
use \Swoole\Coroutine\Http2\Client as H2Client;


/**
 * Class Http2
 * @package HttpServer\Client
 */
class Http2 extends Component
{

	/** @var H2Client[] */
	private $_clients = [];


	/** @var Request[] */
	private $_requests = [];


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return mixed
	 * @throws Exception
	 */
	public function query($domain, $path, $params = [], $timeout = -1)
	{
		$client = $this->getClient($domain, $path, $timeout);
		$client->send($this->getRequest($domain, $path, 'GET', $params));
		return $client->recv();
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param array $params
	 * @param int $timeout
	 * @return mixed
	 * @throws Exception
	 */
	public function push($domain, $path, $params = [], $timeout = -1)
	{
		$client = $this->getClient($domain, $path, $timeout);
		$client->send($this->getRequest($domain, $path, 'POST', $params));
		return $client->recv();
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param $method
	 * @param $params
	 * @return Request
	 * @throws Exception
	 */
	public function getRequest($domain, $path, $method, $params)
	{
		if (isset($this->_requests[$domain . $path])) {
			$req = $this->_requests[$domain . $path];
		} else {
			$req = new Request();
			$this->_requests[$domain . $path] = $req;
		}
		$req->method = $method;
		$req->path = $path;
		$req->headers = [
			'host'            => $domain,
			'user-agent'      => 'Chrome/49.0.2587.3',
			'accept'          => 'text/html,application/json',
			'accept-encoding' => 'gzip'
		];
		if (!is_string($params)) {
			$params = JSON::encode($params);
		}
		$req->data = $params;
		return $req;
	}


	/**
	 * @param $domain
	 * @param $path
	 * @param int $timeout
	 * @return H2Client
	 * @throws Exception
	 */
	private function getClient($domain, $path, $timeout = -1)
	{
		if (isset($this->_clients[$path])) {
			return $this->_clients[$path];
		}
		$client = new H2Client($domain, 443, true);
		$client->set([
			'timeout'       => $timeout,
			'ssl_host_name' => $domain
		]);
		if (!$client->connect()) {
			throw new Exception('Connected fail.');
		}
		return $this->_clients[$domain . $path] = $client;
	}


}
