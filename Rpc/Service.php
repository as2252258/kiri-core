<?php

namespace Rpc;


use Exception;
use HttpServer\Events\OnClose;
use HttpServer\Events\OnConnect;
use HttpServer\Events\OnPacket;
use HttpServer\Events\OnReceive;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Server;
use HttpServer\Service\Http;
use HttpServer\Service\Packet;
use HttpServer\Service\Receive;
use HttpServer\Service\Websocket;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Abstracts\Config;
use Snowflake\Core\Json;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;


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
		$service = Config::get('rpc');
		if (empty($service) || !is_array($service)) {
			return;
		}
		$mode = $service['mode'] ?? SWOOLE_SOCK_TCP6;

		if (Snowflake::port_already($service['port'])) {
			throw new Exception($this->already($service));
		}
		$this->debug(Snowflake::listen($service));

		$this->addCallback($server, $mode);

		$rpcServer = $server->addlistener($service['host'], $service['port'], $mode);
		$rpcServer->set($service['setting'] ?? [
				'open_tcp_keepalive'      => true,
				'tcp_keepidle'            => 30,
				'tcp_keepinterval'        => 10,
				'tcp_keepcount'           => 10,
				'open_http_protocol'      => false,
				'open_websocket_protocol' => false,
			]);
		$this->listenPort($service, $mode);
	}


	/**
	 * @param $service
	 * @return string
	 */
	#[Pure] private function already($service): string
	{
		return sprintf('Port %s::%d is already.', $service['host'], $service['port']);
	}


	/**
	 * @param $server
	 * @param $mode
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	private function addCallback($server, $mode)
	{
		$app = Snowflake::app()->getServer();

		$tcp = [SWOOLE_SOCK_TCP, SWOOLE_TCP, SWOOLE_TCP6, SWOOLE_SOCK_TCP6];

		if (in_array($mode, $tcp) && !$app->isListen(Server::TCP)) {
			$server->on('connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
			$server->on('close', [Snowflake::createObject(OnClose::class), 'onHandler']);
			$server->on('receive', [$class = new OnReceive(), 'onHandler']);
		}

		if (in_array($mode, $tcp) || !$app->isListen(Server::PACKAGE)) {
			$server->on('connect', [Snowflake::createObject(OnConnect::class), 'onHandler']);
			$server->on('close', [Snowflake::createObject(OnClose::class), 'onHandler']);
			$server->on('packet', [$class = new OnPacket(), 'onHandler']);
		}
	}


	/**
	 * @param $service
	 * @param $mode
	 * @throws Exception
	 */
	private function listenPort($service, $mode)
	{
		router()->addPortListen($service['port'], function () use ($service, $mode) {
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
