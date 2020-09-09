<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use HttpServer\Route\Node;
use HttpServer\Service\Http;
use Snowflake\Core\JSON;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * Class OnRequest
 * @package HttpServer\Events
 */
class OnRequest extends Callback
{


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception
	 */
	public function onHandler(Request $request, Response $response)
	{
		try {
			register_shutdown_function(function () use ($response) {
				$error = error_get_last();
				if (!isset($error['type'])) {
					return;
				}
				$types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
				if (!in_array($error['type'], $types)) {
					return;
				}
				$response->status(500);
				$response->end($error['message']);
			});
			/** @var HRequest $sRequest */
			[$sRequest, $sResponse] = static::setContext($request, $response);
			if ($sRequest->is('favicon.ico')) {
				return $params = $sResponse->send($sRequest->isNotFound(), 200);
			}
			return $params = Snowflake::app()->getRouter()->dispatch();
		} catch (Error | \Throwable $exception) {
			$params = $this->sendErrorMessage($sResponse ?? null, $exception, $response);
		} finally {
			$events = Snowflake::app()->getEvent();
			if (!$events->exists(Event::EVENT_AFTER_REQUEST)) {
				return;
			}
			$events->trigger(Event::EVENT_AFTER_REQUEST, [$sRequest, $params]);
		}
	}


	/**
	 * @param $sResponse
	 * @param $exception
	 * @param $response
	 * @return false|int|mixed|string
	 * @throws Exception
	 */
	protected function sendErrorMessage($sResponse, $exception, $response)
	{
		$params = Snowflake::app()->getLogger()->exception($exception);
		if (empty($sResponse)) {
			$sResponse = \response();
			$sResponse->response = $response;
		}
		return $sResponse->send($params, 200);
	}


	/**
	 * @param $request
	 * @param $response
	 * @return array
	 * @throws Exception
	 */
	public static function setContext($request, $response): array
	{
		$request = Context::setContext('request', HRequest::create($request));
		$response = Context::setContext('response', HResponse::create($response));
		return [$request, $response];
	}


}
