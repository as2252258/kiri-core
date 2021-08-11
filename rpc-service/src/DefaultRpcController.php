<?php

namespace Rpc;

use HttpServer\Controller;
use HttpServer\Exception\RequestException;
use Rpc\Annotation\RpcService;
use Server\RequestInterface;


/**
 *
 */
#[RpcService(package: "default", protocol: RpcService::PROTOCOL_JSON, server: 'json-rpc')]
class DefaultRpcController extends Controller
{


	/**
	 * @param RequestInterface $request
	 * @return int
	 * @throws RequestException
	 */
	public function getSystemConfig(RequestInterface $request): int
	{
		return $request->int('a') + $request->int('b');
	}

}
