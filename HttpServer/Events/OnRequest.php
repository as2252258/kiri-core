<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use HttpServer\Route\Router;
use Kiri\Error\Logger;
use Kiri\Event;
use Kiri\Kiri;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

/**
 * Class OnRequest
 * @package HttpServer\Events
 */
class OnRequest extends Callback
{


	public Event $event;
	public Logger $logger;


	public Router $router;


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->router = Kiri::app()->getRouter();
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception
	 */
	public function onHandler(Request $request, Response $response): void
	{
		try {
//			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));

			/** @var HResponse $response */
			[$request, $response] = OnRequest::createContext($request, $response);

			if ($request->is('favicon.ico')) {
				$response->close(404);
			} else {
				$this->router->dispatch();
			}
		} catch (ExitException | Error | Throwable $exception) {
			$this->addError($exception, 'throwable');
			$this->sendErrorMessage($request, $response, $exception);
		}
	}


	/**
	 * @param $request
	 * @param $response
	 * @return array
	 * @throws Exception
	 */
	public static function createContext($request, $response): array
	{
		return [HRequest::create($request), HResponse::create($response)];
	}


	/**
	 * @param $sRequest
	 * @param $sResponse
	 * @param Throwable $exception
	 * @return bool|string
	 * @throws Exception
	 */
	protected function sendErrorMessage($sRequest, $sResponse, Throwable $exception): bool|string
	{
		$this->addError($exception, 'throwable');
		if ($sResponse instanceof Response) {
			[$sRequest, $sResponse] = [HRequest::create($sRequest), HResponse::create($sResponse)];
		}

		$headers = $sRequest->headers->get('access-control-request-headers');
		$methods = $sRequest->headers->get('access-control-request-method');

		/** @var HResponse $sResponse */
		$sResponse->addHeader('Access-Control-Allow-Origin', '*');
		$sResponse->addHeader('Access-Control-Allow-Headers', $headers);
		$sResponse->addHeader('Access-Control-Request-Method', $methods);

		if (!($exception instanceof ExitException)) {
			return $sResponse->send(\logger()->exception($exception), 200);
		} else {
			return $sResponse->send($exception->getMessage(), 200);
		}
	}

}
