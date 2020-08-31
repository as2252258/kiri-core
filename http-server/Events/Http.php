<?php


namespace HttpServer\Events;


use HttpServer\Http\Context;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use HttpServer\ServerManager;
use ReflectionException;
use Snowflake\Application;
use Snowflake\Core\JSON;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Exception;
use Swoole\Http\Server;
use Swoole\Process\Pool;

class Http extends Server
{

	/** @var Application */
	protected $application;


	/**
	 * Receive constructor.
	 * @param $application
	 * @param $host
	 * @param null $port
	 * @param null $mode
	 * @param null $sock_type
	 */
	public function __construct($application, $host, $port = null, $mode = null, $sock_type = null)
	{
		parent::__construct($host, $port, $mode, $sock_type);
		$this->application = $application;
	}


	/**
	 * @param array $settings
	 * @param null $pool
	 * @param array $events
	 * @param array $config
	 * @return mixed|void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function set(array $settings, $pool = null, $events = [], $config = [])
	{
		parent::set($settings);
		Snowflake::get()->set(Pool::class, $pool);
		ServerManager::set($this, $settings, $this->application, $events, $config);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws \Exception
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
			$this->application->debug($dividing_line, 'app');
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
		$this->application->error(var_export($errorInfo, true));

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
