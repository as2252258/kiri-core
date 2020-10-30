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
			Coroutine::defer(function () {
				fire(Event::EVENT_AFTER_REQUEST, [$sRequest ?? null]);

				echo 'Event::EVENT_AFTER_REQUEST ~ defer' . PHP_EOL;
			});
			if (Config::get('debug.enable', false, false)) {
				function_exists('trackerHookMalloc') && trackerHookMalloc();
			}
			/** @var HRequest $sRequest */
			[$sRequest, $sResponse] = [HRequest::create($request), HResponse::create($response)];
			if ($sRequest->is('favicon.ico')) {
				$sResponse->send($sRequest->isNotFound(), 200);
			} else {
				Snowflake::app()->getRouter()->dispatch();
			}
		} catch (Error | \Throwable $exception) {
			$this->sendErrorMessage($sResponse ?? null, $exception, $response);
		}

		echo 'Event::EVENT_AFTER_REQUEST ~~~~~~~~~ defer' . PHP_EOL;
	}

	/**
	 * @param $response
	 * @throws Exception
	 */
	public static function shutdown($response)
	{
		$error = error_get_last();
		if (!isset($error['type'])) {
			return;
		}
		$types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
		if (!in_array($error['type'], $types)) {
			return;
		}
		try {
			$message = $error['message'] . ':' . microtime(true);
			if ($response instanceof Response) {
				$response->status(500);
				$response->end($message);
			}
		} catch (\ErrorException $exception) {
			$logger = Snowflake::app()->logger;
			$logger->write($exception->getMessage(), 'shutdown');
		}
		unset($response);
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

}
