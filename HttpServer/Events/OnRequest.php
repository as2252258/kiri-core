<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use HttpServer\Route\Node;
use HttpServer\Service\Http;
use Snowflake\Abstracts\Config;
use Snowflake\Core\JSON;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
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
		Coroutine::defer(function () {
			fire(Event::EVENT_AFTER_REQUEST);
		});
		$this->onRequest($request, $response);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return false|int|mixed|string|void
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response)
	{
		try {
			/** @var HRequest $sRequest */
			[$sRequest, $sResponse] = [HRequest::create($request), HResponse::create($response)];
			if ($sRequest->is('favicon.ico')) {
				return $sResponse->send($sRequest->isNotFound(), 200);
			}
			return Snowflake::app()->getRouter()->dispatch();
		} catch (Error | \Throwable $exception) {
			return $this->sendErrorMessage($exception);
		} finally {
			$logger = Snowflake::app()->getLogger();
			$logger->write(JSON::encode($request), 'request');
		}
	}


	/**
	 * @param $response
	 * @throws Exception
	 */
	public static function shutdown($response)
	{
		try {
			$error = error_get_last();
			if (!isset($error['type'])) {
				return;
			}
			$types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
			if (!in_array($error['type'], $types)) {
				return;
			}
			$message = $error['message'] . ':' . microtime(true);
			if ($response instanceof Response) {
				$response->status(500);
				$response->end($message);
			}
		} catch (\ErrorException $exception) {
			$logger = Snowflake::app()->logger;
			$logger->write($exception->getMessage(), 'shutdown');
		} finally {
			unset($response);
		}
	}

	/**
	 * @param $exception
	 * @return false|int|mixed|string
	 * @throws ComponentException
	 * @throws Exception
	 */
	protected function sendErrorMessage($exception)
	{
		$params = Snowflake::app()->getLogger()->exception($exception);
		return \response()->send($params, 200);
	}

}
