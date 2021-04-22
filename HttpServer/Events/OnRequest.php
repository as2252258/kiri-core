<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Exception\ExitException;
use HttpServer\Http\Request as HRequest;
use HttpServer\Http\Response as HResponse;
use Snowflake\Error\Logger;
use Snowflake\Event;
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


    public Event $event;
    public Logger $logger;


    /**
     * @throws Exception
     */
    public function init()
    {
        $this->event = Snowflake::app()->getEvent();
        $this->logger = Snowflake::app()->getLogger();
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function onHandler(Request $request, Response $response): mixed
    {
        try {
            defer(function () {
                $this->event->trigger(Event::SYSTEM_RESOURCE_RELEASES);
                $this->logger->insert();
            });
            /** @var HRequest $request */
            [$request, $response] = OnRequest::createContext($request, $response);

            $this->event->dispatch(Event::EVENT_BEFORE_REQUEST, [$request]);

            $result = $request->dispatch();

            $this->event->dispatch(Event::EVENT_AFTER_REQUEST, [$request, $result]);

            return $result;
        } catch (ExitException | Error | \Throwable $exception) {
            $this->addError($exception, 'throwable');
            return $this->sendErrorMessage($request, $response, $exception);
        }
    }


    /**
     * @param $request
     * @param $response
     * @return array
     */
    public static function createContext($request, $response): array
    {
        return [HRequest::create($request), HResponse::create($response)];
    }


    /**
     * @param $sRequest
     * @param $sResponse
     * @param $exception
     * @return bool|string
     * @throws Exception
     */
    protected function sendErrorMessage($sRequest, $sResponse, $exception): bool|string
    {
        $this->addError($exception, 'throwable');
        if ($sResponse instanceof Response) {
            [$sRequest, $sResponse] = [HRequest::create($sRequest), HResponse::create($sResponse)];
        }

        $this->event->dispatch(Event::EVENT_AFTER_REQUEST, [$sRequest, $exception]);

        $headers = $sRequest->headers->get('access-control-request-headers');
        $methods = $sRequest->headers->get('access-control-request-method');

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
