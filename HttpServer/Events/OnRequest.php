<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use ReflectionException;
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
		Coroutine::defer([$this, 'onAfter']);
		try {
			/** @var HRequest $sRequest */
			[$sRequest, $sResponse] = $this->create($request, $response);
			if ($sRequest->is('favicon.ico')) {
				$sResponse->send($sRequest->isNotFound(), 200);
			} else {
				Snowflake::app()->getRouter()->dispatch();
			}
		} catch (ExitException $exception) {
			send($exception->getMessage(), $exception->getCode());
		} catch (Error | \Throwable $exception) {
			$this->sendErrorMessage($exception);
		} finally {
			$logger = Snowflake::app()->getLogger();

			$request = get_object_vars($request);

			$logger->write(Json::encode($request), 'request');
		}
	}


	/**
	 * @throws ComponentException
	 */
	public function onAfter()
	{
		fire(Event::EVENT_AFTER_REQUEST);
	}


	/**
	 * @param $request
	 * @param $response
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function create($request, $response): array
	{
		return [HRequest::create($request), HResponse::create($response)];
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
