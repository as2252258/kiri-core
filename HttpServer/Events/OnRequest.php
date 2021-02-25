<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use ReflectionException;
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
			if (!$request->is('favicon.ico')) {
				return \router()->dispatch();
			}
			return \send(null);
		} catch (ExitException | Error | \Throwable $exception) {
			if ($exception instanceof ExitException) {
				return \send($exception->getMessage(), $exception->getCode());
			}
			return $this->sendErrorMessage($request, $response, $exception);
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
	 * @param $sRequest
	 * @param $sResponse
	 * @param $exception
	 * @return bool|string
	 * @throws ComponentException
	 * @throws Exception
	 */
	protected function sendErrorMessage($sRequest, $sResponse, $exception): bool|string
	{
		$this->error($exception);
		$params = Snowflake::app()->getLogger()->exception($exception);
		if ($sResponse instanceof Response) {
			[$sRequest, $sResponse] = [HRequest::create($sRequest), HResponse::create($sResponse)];
		}

		$sResponse->addHeader('Access-Control-Allow-Origin', '*');
		$sResponse->addHeader('Access-Control-Allow-Headers', $sRequest->headers->get('access-control-request-headers'));
		$sResponse->addHeader('Access-Control-Request-Method', $sRequest->headers->get('access-control-request-method'));

		return $sResponse->send($params, 200);
	}

}
