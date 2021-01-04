<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Annotation\Route\After;
use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Events\Utility\AfterRequest;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use ReflectionException;
use Snowflake\Async;
use Snowflake\Core\Json;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\NotFindClassException;
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
			[$req, $rep] = static::create($request, $response);
			if ($req->is('favicon.ico')) {
				send(null, 404);
			} else {
				router()->dispatch();
			}
		} catch (ExitException | Error | \Throwable $exception) {
			if ($exception instanceof ExitException) {
				send($exception->getMessage(), $exception->getCode());
			} else {
				$this->sendErrorMessage($exception);
			}
		} finally {
			$this->onAfter();
		}
	}


	/**
	 * @param $request
	 * @param $response
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public static function create($request, $response): array
	{
		return [HRequest::create($request), HResponse::create($response)];
	}


	/**
	 * @throws ComponentException
	 */
	public function onAfter()
	{
		fire(Event::EVENT_AFTER_REQUEST);
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
	 * @return bool|string
	 * @throws ComponentException
	 * @throws Exception
	 */
	protected function sendErrorMessage($exception): bool|string
	{

		$sRequest = \request();
		$sResponse = \response();

		$sResponse->addHeader('Access-Control-Allow-Origin', '*');
		$sResponse->addHeader('Access-Control-Allow-Headers', $sRequest->headers->get('access-control-request-headers'));
		$sResponse->addHeader('Access-Control-Request-Method', $sRequest->headers->get('access-control-request-method'));

		$params = Snowflake::app()->getLogger()->exception($exception);
		return $sResponse->send($params, 200);
	}

}
