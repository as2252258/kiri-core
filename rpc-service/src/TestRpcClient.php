<?php

namespace Rpc;

use Annotation\Rpc\RpcConsumer;
use Kiri\Exception\NotFindClassException;
use Rpc\Annotation\RpcService;


/**
 *
 */
#[RpcConsumer(package: 'default', method: '', timeout: 10, mode: 'json-rpc')]
class TestRpcClient
{

	public string $package = 'default';


	public string $protocol = RpcService::PROTOCOL_JSON;


	public int $timeout = 10;


	public string $method = '';


	/**
	 * @return array
	 */
	protected function getRegistry(): array
	{
		return ['127.0.0.1', 5537];
	}


	/**
	 * @throws NotFindClassException
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function dispatch(array $param)
	{
		[$host, $port] = $this->getRegistry();

		$client = di(Client::class);
		$client->config = ['host' => $host, 'port' => $port, 'timeout' => $this->timeout];
		$client->dispatch($this->package, $this->method, $param);
	}

}
