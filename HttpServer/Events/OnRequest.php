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
	public function onHandler(Request $request, Response $response): mixed
	{
		try {
			Coroutine::defer(function () use ($request) {
				fire(Event::SYSTEM_RESOURCE_RELEASES);
			});
			[$request, $response] = static::create($request, $response);
			if ($request->is('favicon.ico')) {
				return \send(null, 404);
			}
			return \router()->dispatch();
		} catch (ExitException | Error | \Throwable $exception) {
			$this->addError($exception);
			if ($exception instanceof ExitException) {
				return \send($exception->getMessage(), $exception->getCode());
			}
			return $this->sendErrorMessage($exception);
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
			$logger = Snowflake::app()->getLogger();
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
