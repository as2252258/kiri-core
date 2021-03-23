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

		if (Snowflake::port_already($service['port'])) {
			throw new Exception(sprintf('Port %s::%d is already.', $service['host'], $service['port']));
		}
		$this->debug(sprintf('Port %s::%d is already.', $service['host'], $service['port']));

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
				/** @var Request $request */
				$request = Context::getContext('request');
				if (($node = router()->find_path(Service::replace($request, $service))) === null) {
					throw new Exception('Cmd not find.');
				}
				$response = $node->dispatch();
				if (is_string($response)) {
					return $response;
				}
				return Json::encode($response);
			} catch (\Throwable $exception) {
				$this->addError($exception);
				return Json::encode(['state' => 'fail', 'message' => $exception->getMessage()]);
			}
		});
	}


	/**
	 * @param Request $request
	 * @param array $service
	 * @return Request
	 * @throws Exception
	 */
	public static function replace(Request $request, array $service): Request
	{
		$body = $request->params->getBodyAndClear();
		if (is_string($body) && is_null($body = Json::decode($body))) {
			throw new Exception('Protocol format error.');
		}

		if (!isset($body['cmd'])) {
			throw new Exception('Unknown system cmd.');
		}
		$request->params->setPosts($body);

		$body['cmd'] = ltrim($body['cmd'], '/');

		$header = $request->headers;
		$header->replace('request_uri', 'rpc/p' . $service['port'] . '/' . $body['cmd']);
		$header->replace('request_method', Request::HTTP_CMD);
		$request->parseUri();

		return $request;
	}


}
