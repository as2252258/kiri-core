<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use HttpServer\Service\Http;
use Snowflake\Core\JSON;
use Snowflake\Snowflake;
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
	 * @throws Exception
	 */
	public function onHandler(Request $request, Response $response)
	{
		try {
			[$sRequest, $sResponse] = static::setContext($request, $response);
			$sResponse->send(Snowflake::get()->router->dispatch(), 200);
		} catch (Error | \Throwable $exception) {
			if (!isset($sResponse)) {
				$response->status(200);
				$response->end($exception->getMessage());
			} else {
				$sResponse->send($this->format($exception), 200);
			}
		} finally {
			$dividing_line = str_pad('', 100, '-');
			$this->debug($dividing_line, 'app');
		}
	}


	/**
	 * @param $exception
	 * @return false|int|mixed|string
	 * @throws Exception
	 */
	public function format($exception)
	{
		$errorInfo = [
			'message' => $exception->getMessage(),
			'file'    => $exception->getFile(),
			'line'    => $exception->getLine()
		];
		$this->error(var_export($errorInfo, true));

		$code = $exception->getCode() ?? 500;
		$trance = array_slice($exception->getTrace(), 0, 10);
		Snowflake::get()->logger->write(print_r($trance, true), 'exception');

		return JSON::to($code, $errorInfo['message']);
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
