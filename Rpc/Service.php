<?php

namespace Rpc;


use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class Service
 * @package Rpc
 */
class Service extends Component
{


	/**
	 * @param Packet|Websocket|Receive|Http|null $server
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function instance(Packet|Websocket|Receive|null|Http $server): void
	{
		$services = Config::get('rpc.service', false, []);
		if (empty($services)) {
			return;
		}
		$router = Snowflake::app()->getRouter();
		foreach ($services as $service) {
			$this->addService($router, $server, $service);
		}
	}


	/**
	 * @param $router
	 * @param $server
	 * @param $service
	 * @throws Exception
	 */
	private function addService($router, $server, $service)
	{
		$mode = $service['mode'] ?? SWOOLE_SOCK_TCP6;
		$rpcServer = $server->addlistener($service['host'], $service['port'], $mode);
		$rpcServer->set([
			'open_tcp_keepalive'      => true,
			'tcp_keepidle'            => 30,
			'tcp_keepinterval'        => 10,
			'tcp_keepcount'           => 10,
			'open_http_protocol'      => false,
			'open_websocket_protocol' => false,
		]);
		$router->addPortListen($service['port'], function () use ($service, $mode) {
			try {
				$request = Context::getContext('request');
				$request->headers->replace('request_method', Request::HTTP_CMD);

				$router = Snowflake::app()->getRouter();
				if (($node = $router->find_path($request)) === null) {
					throw new Exception('Cmd not find.');
				}
				return $node->dispatch();
			} catch (\Throwable $exception) {
				$this->addError($exception);
				return serialize(['state' => 'fail', 'message' => $exception->getMessage()]);
			} finally {
				fire(Event::SYSTEM_RESOURCE_RELEASES);
			}
		});
	}


}
